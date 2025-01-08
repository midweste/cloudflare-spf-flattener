<?php

declare(strict_types=1);

namespace CloudflareSpf;

use CloudflareSpf\Trait\{CloudflareApi, ChannelLogger, Logger};

class AccountFlattener
{
    use CloudflareApi, ChannelLogger;

    protected $apiToken;
    protected $excluded = [];
    protected $order = [];

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

    public function setOrder(array $order): self
    {
        $this->order = $order;
        return $this;
    }

    public function addOrder(string $order): self
    {
        $this->order[] = $order;
        return $this;
    }

    public function getOrder(): array
    {
        return array_values($this->order);
    }

    public function flatten(): array
    {
        $zones = $this->getZones();
        $flattened = [];

        $orderedZones = [];
        foreach ($this->getOrder() as $zone) {
            foreach ($zones->result as $zoneObj) {
                if ($zoneObj->name === $zone) {
                    $orderedZones[] = $zoneObj;
                }
            }
        }
        foreach ($zones->result as $zone) {
            if (!in_array($zone->name, $this->order)) {
                $orderedZones[] = $zone;
            }
        }

        $this->notice('Start account spf flattening');
        foreach ($orderedZones as $zone) {
            $zoneName = $zone->name;
            if (in_array($zoneName, $this->excluded)) {
                $this->warning(sprintf('Excluded %s.  Skipping', $zoneName));
                continue;
            }
            $flattener = new ZoneFlattener($zoneName, $this->getApiToken());
            $flattener->setLogger($this->logger())->flatten();
        }
        $this->notice('Finished account spf flattening');
        return $flattened;
    }
}
