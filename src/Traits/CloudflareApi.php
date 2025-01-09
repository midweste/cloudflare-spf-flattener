<?php

declare(strict_types=1);

namespace CloudflareSpf\Traits;

use Cloudflare\API\Adapter\Guzzle;
use Cloudflare\API\Auth\APIToken;
use Cloudflare\API\Endpoints\DNS;
use Cloudflare\API\Endpoints\Zones;

trait CloudflareApi
{
    abstract function getApiToken(): string;

    protected function getApiAdapter(): Guzzle
    {
        return new Guzzle(new APIToken($this->getApiToken()));
    }

    protected function getApiZones(): Zones
    {
        return new Zones($this->getApiAdapter());
    }

    protected function getApiZoneId(string $zone): string
    {
        $zone = $this->getApiZones()->listZones($zone);
        if (count($zone->result) === 0 || !isset($zone->result[0]->id)) {
            throw new \Exception('No zone found using this api token for domain ' . $zone);
        }
        return $zone->result[0]->id;
    }

    protected function getApiDns(): DNS
    {
        return new DNS($this->getApiAdapter());
    }
}
