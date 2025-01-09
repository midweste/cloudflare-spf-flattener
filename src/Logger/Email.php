<?php

namespace CloudflareSpf\Logger;

use CloudflareSpf\Traits\ChannelLogger;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Exception\RuntimeException;

class Email implements LoggerInterface
{
    use ChannelLogger;

    protected $settings;
    protected $sslMap = [
        'none' => PHPMailer::ENCRYPTION_SMTPS,
        'tls' => PHPMailer::ENCRYPTION_STARTTLS,
        'ssl' => PHPMailer::ENCRYPTION_SMTPS
    ];

    public function __construct(array $settings)
    {
        $this->setSettings($settings);
    }

    public function __destruct()
    {
        $logs = $this->logEntries();

        $settings = $this->getSettings();
        $level = $settings['log_level'] ?? 'error';
        $filtered = $this->setLogLevel($level)->logEntriesFiltered();

        // none over level threshold
        if (empty($filtered)) {
            return;
        }

        $subject = '[Cloudflare SPF Flattener] ' . ucfirst($level);
        $body = '';
        foreach ($logs as $message) {
            $body .= $this->logFormatMessage($message['level'], $message['message'], $message['context']) . PHP_EOL;
        }

        return $this->send($subject, $body);
    }

    protected function getSettings(): array
    {
        return $this->settings;
    }

    protected function setSettings(array $settings): self
    {
        $requiredSettings = ['host', 'log_level', 'username', 'password', 'port', 'ssl', 'from_email', 'to_email'];

        foreach ($requiredSettings as $setting) {
            if (empty($settings[$setting])) {
                throw new RuntimeException("Missing required setting: $setting");
            }
        }

        if (!is_string($settings['host'])) {
            throw new RuntimeException("Invalid type for setting: host");
        }

        $levels = $this->logLevels();
        if (!isset($levels[$settings['log_level']])) {
            $levelsString = implode(', ', array_keys($levels));
            throw new RuntimeException("Invalid type for setting: log_level. Must be one of: $levelsString");
        }
        if (!is_string($settings['username'])) {
            throw new RuntimeException("Invalid type for setting: username");
        }
        if (!is_string($settings['password'])) {
            throw new RuntimeException("Invalid type for setting: password");
        }
        if (!is_int($settings['port'])) {
            throw new RuntimeException("Invalid type for setting: port");
        }
        if (!in_array($settings['ssl'], array_keys($this->sslMap))) {
            $values = implode(', ', array_keys($this->sslMap));
            throw new RuntimeException("Invalid type for setting: ssl. Must be one of: $values");
        }
        if (!filter_var($settings['from_email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("Invalid email format for setting: from_email");
        }
        if (!filter_var($settings['to_email'], FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException("Invalid email format for setting: to_email");
        }

        $this->settings = $settings;
        return $this;
    }

    protected function send(string $subject, string $body): bool
    {
        $settings = $this->getSettings();

        $mail = new PHPMailer(true);
        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = $settings['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $settings['username'];
            $mail->Password = $settings['password'];
            $mail->SMTPSecure = $this->sslMap[$settings['ssl']];
            $mail->Port = $settings['port'];

            // Recipients
            $mail->setFrom($settings['from_email']);
            $mail->addAddress($settings['to_email']);

            // Content
            $mail->isHTML(false);
            $mail->Subject = $subject;
            $mail->Body = $body;

            $mail->send();
            return true;
        } catch (Exception $e) {
            throw new RuntimeException("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        }
        return false;
    }

    public function log($level, $message, array $context = []): void
    {
        $this->logs[] = [
            'level' => $level,
            'message' => $message,
            'context' => $context
        ];
    }
}
