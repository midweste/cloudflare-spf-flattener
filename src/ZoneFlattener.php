<?php

declare(strict_types=1);

namespace CloudflareSpf;

use CloudflareSpf\Traits\CloudflareApi;
use CloudflareSpf\Traits\ChannelLogger;
use SpfLibFlattener\RecordSplitter;
use SpfLibFlattener\SpfFlattener;

class ZoneFlattener
{
    use CloudflareApi, ChannelLogger;

    private $zone;
    private $apiToken;

    public function __construct(string $zone, string $apiToken)
    {
        $this->zone = $zone;
        $this->apiToken = $apiToken;
    }

    protected function getApiToken(): string
    {
        return $this->apiToken;
    }

    protected function getZoneName(): string
    {
        return $this->zone;
    }

    protected function getZoneId(): string
    {
        return $this->getApiZoneId($this->getZoneName());
    }

    public function getDnsRecordsByType(string $type): object
    {
        return $this->listRecords($this->getZoneId(), ['type' => $type], 1, 5000000);
    }

    public function getDnsTxtRecords(): object
    {
        return $this->getDnsRecordsByType('TXT');
    }

    /**
     * Api listRecords from Cloudflare SDK broken and calling deprecated methods
     * Stand in replacement allowing for all params to be passed
     * @see
     * https://developers.cloudflare.com/fundamentals/api/reference/deprecations/#2025-02-21
     */
    public function listRecords(string $zoneID, array $params = [], int $page = 1, int $perPage = 20, string $order = '', string $direction = '', string $match = 'all'): \stdClass
    {
        $query = [
            'page' => $page,
            'per_page' => $perPage,
            'match' => $match
        ];

        if (!empty($order)) {
            $query['order'] = $order;
        }

        if (!empty($direction)) {
            $query['direction'] = $direction;
        }

        $query = array_merge($query, $params);

        $user = $this->getApiAdapter()->get('zones/' . $zoneID . '/dns_records', $query);
        $body = json_decode($user->getBody()->getContents());

        return (object)['result' => $body->result, 'result_info' => $body->result_info];
    }


    protected function getDnsTxtSearch(string $name = '', string $contains = ''): object
    {
        $zone = $this->getZoneName();

        $params = [
            'name' => $name,
            'type' => 'TXT',
        ];
        if (!empty($contains)) {
            $params['content.contains'] = $contains;
        }

        $records = $this->listRecords($this->getApiZoneId($zone), $params, 1, 5000000);
        if (count($records->result) > 1) {
            throw new \Exception(sprintf('Multiple SPF records found using %s for domain %s', $name, $zone));
        }
        return count($records->result) > 0 ? current($records->result) : new \stdClass();
    }

    protected function txtRemoveQuotes(string $content): string
    {
        $content = trim($content);
        if (substr($content, 0, 1) === '"' && substr($content, -1) === '"') {
            return substr($content, 1, -1);
        }
        return $content;
    }

    protected function splitSpfStub(string $beginsWith = 'v=spfmaster', int $charsMax = 2048, string $pattern = 'spf#'): array
    {
        $zone = $this->getZoneName();

        $stubSpf = $this->getDnsTxtSearch($zone, $beginsWith);
        if (!isset($stubSpf->content)) {
            $this->warning(sprintf('No Stub SPF record found using %s for domain %s', $beginsWith, $zone));
            return [];
        }

        $stubSpf = $this->txtRemoveQuotes($stubSpf->content);
        // replace stub SPF record $beginsWith with v=spf1
        $stubReplaced = str_replace($beginsWith, 'v=spf1', $stubSpf);

        // flatten and split
        $flattener = SpfFlattener::createFromText($zone, $stubReplaced);
        $splitter = new RecordSplitter($flattener->toFlatRecord());
        $split = $splitter->split($charsMax, $pattern . '.' . $zone);

        return $split;
    }

    public function flatten(string $stubBeginsWith = 'v=spfmaster', int $charsMax = 2048, string $pattern = 'spf#'): array
    {
        $zone = $this->getZoneName();
        $this->notice('Started splitting SPF records for ' . $zone);

        $results = [];

        $spfs = $this->splitSpfStub($stubBeginsWith, $charsMax, $pattern);
        if (count($spfs) < 2) {
            $this->warning(sprintf('Problem splitting SPF record or record doesnt need splitting for zone %s', $zone));
            return [];
        }

        // update/create sub spf records first
        foreach ($spfs as $domain => $value) {
            if ($domain === 'primary') {
                continue;
            }
            $subRecord = $this->getDnsTxtSearch($domain);
            $result = $this->addUpdateRecord($subRecord, $domain, $value);
            if ($result === false) {
                throw new \Exception(sprintf('Problem updating record for %s', $domain));
            }
            $results[$domain] = $result;
        }

        // update/create primary spf record
        $primaryRecord = $this->getDnsTxtSearch($zone, 'v=spf1');
        $result = $this->addUpdateRecord($primaryRecord, $zone, $spfs['primary']);
        if ($result === false) {
            throw new \Exception('Problem updating primary SPF record');
        }
        $results[$zone] = $result;

        $this->notice('Finished splitting SPF records for ' . $zone);
        return $results;
    }

    protected function addUpdateRecord(object $record, string $name, string $newContent): bool
    {
        $zoneId = $this->getZoneId();
        $ttl = 60;
        $this->info('Adding/Updating record for ' . $name);

        // create
        if (empty($record->id)) {
            $result = $this->getApiDns()->addRecord($zoneId, 'TXT', $name, $newContent, $ttl, false);
            $this->notice('New record created for ' . $name);
            return $result;
        }

        // update
        if ($record->content === $newContent) {
            $this->debug('No change needed for ' . $name);
            return true;
        }
        $this->debug('Changes detected in record for ' . $name);

        $record->ttl = $ttl;
        $record->comment = 'DO NOT EDIT!! Autogenerated by CloudflareSpf.  Edit the stub TXT record starting with v=spfmaster';
        $record->content = $newContent;
        $updated = $this->getApiDns()->updateRecordDetails($zoneId, $record->id, (array) $record);
        if (!isset($updated->success) || $updated->success !== true) {
            $errors = [
                'Problem updating record for ' . $name
            ];
            if (isset($updated->errors)) {
                foreach ($updated->errors as $error) {
                    $errors[] = isset($error->message) ? $error->message : 'Unknown error';
                }
            }
            throw new \Exception(sprintf('Problem updating record for %s: %s', $name, implode(', ', $errors)));
        }
        $this->notice('Record added/updated for ' . $name);
        return true;
    }
}
