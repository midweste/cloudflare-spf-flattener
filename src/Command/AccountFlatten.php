<?php

declare(strict_types=1);

namespace CloudflareSpf\Command;

use CloudflareSpf\AccountFlattener;
use CloudflareSpf\Logger\MultiOutput;
use CloudflareSpf\Command\AbstractCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\{InputInterface, InputArgument};
use Symfony\Component\Console\Output\OutputInterface;

class AccountFlatten extends AbstractCommand
{
    protected static $defaultName = 'account:flatten';

    protected function configure()
    {
        $this
            ->setDescription('Flattens SPF records for an account.')
            ->setHelp('This command allows you to flatten SPF records for an account.')
            ->addArgument('settings-json', InputArgument::REQUIRED, 'Path to the settings JSON file')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $multiLogger = new MultiOutput([$output]);
        try {
            $flattener = new AccountFlattener($this->getApiToken($input));
            $flattener->setLogger($multiLogger);

            // channel notification loggers
            foreach ($this->getNotificationChannels($input) as $channel) {
                $loggerClass = sprintf('\CloudflareSpf\Logger\%s', ucfirst($channel));
                if (!class_exists($loggerClass)) {
                    throw new RuntimeException("Logger class not found: $loggerClass");
                }
                $multiLogger->addLogger(new $loggerClass($this->getNotificationChannelSettings($input, $channel)));
            }

            // excluded zones
            foreach ($this->getZoneExclusions($input) as $exclude) {
                $flattener->addExcluded($exclude);
            }

            // ordered zones
            foreach ($this->getZoneOrdering($input) as $order) {
                $flattener->addOrder($order);
            }

            $flattener->flatten();
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $multiLogger->critical(sprintf('%s in %s on %d %s', $e->getMessage(), $e->getFile(), $e->getLine(), PHP_EOL . $e->getTraceAsString()));
            return Command::FAILURE;
        }
    }
}
