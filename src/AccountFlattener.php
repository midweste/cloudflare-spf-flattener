<?php

declare(strict_types=1);

namespace CloudflareSpf;

use CloudflareSpf\Trait\{CloudflareApi, Logger};
use Psr\Log\LoggerInterface;

class AccountFlattener  implements LoggerInterface
{
    use CloudflareApi, Logger;

    protected $apiToken;
    protected $excluded = [];

    public function __construct(string $apiToken)
    {
        $this->apiToken = $apiToken;
    }

    protected function getApiToken(): string
    {
        return $this->apiToken;
    }

    public function setExcluded(array $excluded): self
    {
        $this->excluded = $excluded;
        return $this;
    }

    public function addExcluded(string $excluded): self
    {
        $this->excluded[] = $excluded;
        return $this;
    }

    public function getExcluded(): array
    {
        return array_values($this->excluded);
    }

    public function getZones(): object
    {
        return $this->getApiZones()->listZones();
    }

    public function flatten(): array
    {
        $zones = $this->getZones();
        $flattened = [];

        $this->notice('Start account spf flattening');
        foreach ($zones->result as $zone) {
            $zoneName = $zone->name;
            if (in_array($zoneName, $this->excluded)) {
                $this->warning(sprintf('Excluded %s.  Skipping', $zoneName));
                continue;
            }
            $flattener = new ZoneFlattener($zoneName, $this->getApiToken());
            $flattener->flatten();
        }
        $this->notice('Finished account spf flattening');
        return $flattened;
    }
}
