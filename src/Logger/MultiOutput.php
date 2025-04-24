<?php

namespace CloudflareSpf\Logger;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MultiOutput extends AbstractLogger
{
    private $outputs = [];
    private $loggers = [];

    public function __construct(array $outputs = [], array $loggers = [])
    {
        $this->outputs = $outputs;
        $this->loggers = $loggers;
    }

    public function addOutput(OutputInterface $output): void
    {
        $this->outputs[] = $output;
    }

    public function addLogger(LoggerInterface $logger): void
    {
        $this->loggers[] = $logger;
    }

    protected function format(string $level, string $message, array $context = []): string
    {
        $messageContext = '';
        if (!empty($context)) {
            $messageContext = json_encode($context);
            if (false === $messageContext) {
                $messageContext = json_encode(['message' => 'Failed to encode context']);
            }
        }

        $template = '%s: %s %s';
        $message = sprintf($template, strtoupper($level), $message, $messageContext);
        return $message;
    }

    public function log($level, $message, array $context = []): void
    {
        // Output to all outputs
        foreach ($this->outputs as $output) {
            $output->writeln($this->format($level, $message, $context));
        }

        // Log to all loggers
        foreach ($this->loggers as $logger) {
            $logger->log($level, $message, $context);
        }
    }
}
