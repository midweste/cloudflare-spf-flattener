<?php

declare(strict_types=1);

namespace CloudflareSpf\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\InputInterface;

abstract class AbstractCommand extends Command
{
    protected $logger;

    protected function getApiToken(InputInterface $input): string
    {
        $apiToken = false;
        $settings = $this->getSettings($input);

        if (!isset($settings['api_token'])) {
            throw new RuntimeException('api_token not found in settings JSON file.');
        }
        $apiToken = $settings['api_token'];

        if (!is_string($apiToken) || empty($apiToken)) {
            throw new RuntimeException('api_token provided is empty or not a string.');
        }
        return $apiToken;
    }

    protected function getSettings(InputInterface $input): array
    {
        $settingsPath = $input->getArgument('settings-json');
        if (!is_readable($settingsPath)) {
            throw new RuntimeException('Settings JSON file is not readable or does not exist.');
        }

        $settings = json_decode(file_get_contents($settingsPath), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Error decoding JSON from settings file: ' . json_last_error_msg());
        }
        return $settings;
    }

    protected function getNotificationChannels(InputInterface $input): array
    {
        $settings = $this->getSettings($input);
        if (!empty($settings['notifications']['channels']) && is_array($settings['notifications']['channels'])) {
            return $settings['notifications']['channels'];
        }
        return [];
    }

    protected function getNotificationChannelSettings(InputInterface $input, string $channel): array
    {
        $settings = $this->getSettings($input);
        if (!empty($settings['notifications']['settings'][$channel]) && is_array($settings['notifications']['settings'][$channel])) {
            return $settings['notifications']['settings'][$channel];
        }
        return [];
    }

    protected function getZoneExclusions(InputInterface $input): array
    {
        $settings = $this->getSettings($input);
        if (!empty($settings['zones']['excluded']) && is_array($settings['zones']['excluded'])) {
            return $settings['zones']['excluded'];
        }
        return [];
    }

    protected function getZoneOrdering(InputInterface $input): array
    {
        $settings = $this->getSettings($input);
        if (!empty($settings['zones']['order']) && is_array($settings['zones']['order'])) {
            return $settings['zones']['order'];
        }
        return [];
    }
}
