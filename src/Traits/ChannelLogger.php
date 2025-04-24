<?php

declare(strict_types=1);

namespace CloudflareSpf\Traits;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

trait ChannelLogger
{
    protected $logger;
    protected $logs = [];
    protected $logLevel = LogLevel::INFO;
    // protected string $logErrorLevel = LogLevel::WARNING;
    protected $logLevels = [
        LogLevel::EMERGENCY => 0,
        LogLevel::ALERT => 1,
        LogLevel::CRITICAL => 2,
        LogLevel::ERROR => 3,
        LogLevel::WARNING => 4,
        LogLevel::NOTICE => 5,
        LogLevel::INFO => 6,
        LogLevel::DEBUG => 7,
    ];

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    public function logger(): LoggerInterface
    {
        return $this->logger;
    }

    public function logEntries(): array
    {
        return $this->logs;
    }

    public function logEntriesFiltered(): array
    {
        $levels = $this->logLevels;
        $threshold = $levels[$this->logLevel()];

        $filtered = [];
        foreach ($this->logEntries() as $log) {
            $logLevelNumber = $levels[$log['level']];
            if ($logLevelNumber <= $threshold) {
                $filtered[] = $log;
            }
        }
        return $filtered;
    }

    public function setLogLevel(string $level): self
    {
        if (!array_key_exists($level, $this->logLevels)) {
            throw new \InvalidArgumentException("Invalid log level: $level");
        }
        $this->logLevel = $level;
        return $this;
    }

    public function logLevel(): string
    {
        return $this->logLevel;
    }

    public function logLevels(): array
    {
        return $this->logLevels;
    }

    protected function logFormatMessage(string $level, string $message, array $context = []): string
    {
        $messageContext = '';
        if (!empty($context)) {
            $messageContext = var_dump($context, true);
        }

        $template = '%s: %s %s';
        $message = sprintf($template, strtoupper($level), $message, $messageContext);
        return $message;
    }

    /**
     * System is unusable.
     */
    public function emergency($message, array $context = [])
    {
        $this->logger->log(LogLevel::EMERGENCY, $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     */
    public function alert($message, array $context = [])
    {
        $this->logger->log(LogLevel::ALERT, $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     */
    public function critical($message, array $context = [])
    {
        $this->logger->log(LogLevel::CRITICAL, $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     */
    public function error($message, array $context = [])
    {
        $this->logger->log(LogLevel::ERROR, $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     */
    public function warning($message, array $context = [])
    {
        $this->logger->log(LogLevel::WARNING, $message, $context);
    }

    /**
     * Normal but significant events.
     */
    public function notice($message, array $context = [])
    {
        $this->logger->log(LogLevel::NOTICE, $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     */
    public function info($message, array $context = [])
    {
        $this->logger->log(LogLevel::INFO, $message, $context);
    }

    /**
     * Detailed debug information.
     */
    public function debug($message, array $context = [])
    {
        $this->logger->log(LogLevel::DEBUG, $message, $context);
    }
}
