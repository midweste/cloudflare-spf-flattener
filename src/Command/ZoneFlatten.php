<?php

declare(strict_types=1);

namespace CloudflareSpf\Command;

use CloudflareSpf\Command\AbstractCommand;
use CloudflareSpf\Logger\MultiOutput;
use CloudflareSpf\ZoneFlattener;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\{InputArgument, InputInterface};
use Symfony\Component\Console\Output\OutputInterface;

class ZoneFlatten extends AbstractCommand
{
    protected static $defaultName = 'zone:flatten';

    protected function configure()
    {
        $this
            ->setDescription('Flattens SPF records for a domain.')
            ->setHelp('This command allows you to flatten SPF records for a specified domain...')
            ->addArgument('settings-json', InputArgument::REQUIRED, 'Path to the settings JSON file')
            ->addArgument('domain', InputArgument::REQUIRED, 'Domain to flatten SPF records for')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $multiLogger = new MultiOutput([$output]);
        try {
            $domain = $input->getArgument('domain');
            if (!filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
                throw new RuntimeException('Invalid domain provided.');
            }

            $flattener = new ZoneFlattener($domain, $this->getApiToken($input));
            $flattener->setLogger($multiLogger)->flatten();
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $multiLogger->critical(sprintf('%s in %s on %d %s', $e->getMessage(), $e->getFile(), $e->getLine(), PHP_EOL . $e->getTraceAsString()));
            return Command::FAILURE;
        }
    }
}
