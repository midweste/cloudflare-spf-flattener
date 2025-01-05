<?php

declare(strict_types=1);

namespace CloudflareSpf\Command;

use CloudflareSpf\AccountFlattener;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;

class AccountFlatten extends Command
{
    protected static $defaultName = 'account:flatten';

    protected function configure()
    {
        $this
            ->setDescription('Flattens SPF records for an account.')
            ->setHelp('This command allows you to flatten SPF records for an account.')
            ->addOption('cloudflare-json', null, InputOption::VALUE_OPTIONAL, 'Path to the Cloudflare JSON credentials file (optional)')
            ->addOption('api-token', null, InputOption::VALUE_OPTIONAL, 'Cloudflare API token (optional)');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cloudflareJsonPath = $input->getOption('cloudflare-json');
        $apiToken = $input->getOption('api-token');

        if ($apiToken === null && $cloudflareJsonPath === null) {
            throw new RuntimeException('Either the Cloudflare JSON file or the API token must be provided.');
        }

        if ($apiToken === null) {
            $creds = json_decode(file_get_contents($cloudflareJsonPath), true);
            $apiToken = $creds['api_token'];
        }
        $spff = new AccountFlattener($apiToken);
        $spff->flatten();

        return Command::SUCCESS;
    }
}
