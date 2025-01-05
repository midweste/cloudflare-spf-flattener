<?php

declare(strict_types=1);

namespace CloudflareSpf\Trait;

use Psr\Log\{LoggerInterface, LogLevel};

trait Logger
{
    protected $logger;
    protected $handler;
    protected $logLevel = LogLevel::INFO;

    public function setLogger(LoggerInterface $logger): self
    {
        $this->logger = $logger;
        return $this;
    }

    public function getLogger(): LoggerInterface
    {
        return $this->logger;
    }

    public function setLoggerHandler(callable $handler): self
    {
        $this->handler = $handler;
        return $this;
    }

    public function getLoggerHandler(): callable
    {
        if (!is_callable($this->handler)) {
            $this->handler = function ($level, string|\Stringable $message, array $context = []): void {
                // default to simple echo logger
                $interpolate = function (string|\Stringable $message, array $context = []): string {
                    $replace = [];
                    foreach ($context as $key => $val) {
                        $replace['{' . $key . '}'] = $val;
                    }
                    return strtr($message, $replace);
                };
                $message = $interpolate($message, $context);

                $datetime = new \DateTime();
                echo $datetime->format('Y-m-d H:i:s') . ' ' . strtoupper($level) . ': ' . $message . PHP_EOL;
            };
        }
        return $this->handler;
    }

    public function setLoggerLevel(string $level): self
    {
        $this->logLevel = $level;
        return $this;
    }

    public function getLoggerLevel(): string
    {
        return $this->logLevel;
    }

    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::EMERGENCY, $message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ALERT, $message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::CRITICAL, $message, $context);
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::ERROR, $message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::WARNING, $message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::NOTICE, $message, $context);
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::INFO, $message, $context);
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(LogLevel::DEBUG, $message, $context);
    }

    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $logLevels = [
            LogLevel::EMERGENCY => 0,
            LogLevel::ALERT => 1,
            LogLevel::CRITICAL => 2,
            LogLevel::ERROR => 3,
            LogLevel::WARNING => 4,
            LogLevel::NOTICE => 5,
            LogLevel::INFO => 6,
            LogLevel::DEBUG => 7,
        ];

        if ($logLevels[$level] <= $logLevels[$this->getLoggerLevel()]) {
            $this->getLoggerHandler()($level, $message, $context);
        }
    }
}
