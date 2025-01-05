<?php

declare(strict_types=1);

namespace CloudflareSpf\Command;

use CloudflareSpf\ZoneFlattener;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\{InputArgument, InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;

class ZoneFlatten extends Command
{
    protected static $defaultName = 'zone:flatten';

    protected function configure()
    {
        $this
            ->setDescription('Flattens SPF records for a domain.')
            ->setHelp('This command allows you to flatten SPF records for a specified domain...')
            ->addArgument('domain', InputArgument::REQUIRED, 'Domain to flatten SPF records for')
            ->addOption('cloudflare-json', null, InputOption::VALUE_OPTIONAL, 'Path to the Cloudflare JSON credentials file (optional)')
            ->addOption('api-token', null, InputOption::VALUE_OPTIONAL, 'Cloudflare API token (optional)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $domain = $input->getArgument('domain');
        $cloudflareJsonPath = $input->getOption('cloudflare-json');
        $apiToken = $input->getOption('api-token');

        if ($apiToken === null && $cloudflareJsonPath === null) {
            throw new RuntimeException('Either the Cloudflare JSON file or the API token must be provided.');
        }

        if ($apiToken === null) {
            $creds = json_decode(file_get_contents($cloudflareJsonPath), true);
            $apiToken = $creds['api_token'];
        }

        $spff = new ZoneFlattener($domain, $apiToken);
        $spff->flatten();

        return Command::SUCCESS;
    }
}
