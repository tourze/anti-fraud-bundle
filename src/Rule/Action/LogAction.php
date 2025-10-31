<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Rule\Action;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[WithMonologChannel(channel: 'anti_fraud')]
#[Autoconfigure(public: true)]
class LogAction implements ActionInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private string $logLevel = 'info',
        private string $message = 'Anti-fraud detection triggered',
    ) {
    }

    public function execute(Request $request): ?Response
    {
        $context = [
            'path' => $request->getPathInfo(),
            'method' => $request->getMethod(),
            'ip' => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent', ''),
            'timestamp' => time(),
        ];

        match ($this->logLevel) {
            'debug' => $this->logger->debug($this->message, $context),
            'info' => $this->logger->info($this->message, $context),
            'notice' => $this->logger->notice($this->message, $context),
            'warning' => $this->logger->warning($this->message, $context),
            'error' => $this->logger->error($this->message, $context),
            'critical' => $this->logger->critical($this->message, $context),
            'alert' => $this->logger->alert($this->message, $context),
            'emergency' => $this->logger->emergency($this->message, $context),
            default => $this->logger->info($this->message, $context),
        };

        // 仅记录，不影响请求
        return null;
    }

    public function getName(): string
    {
        return 'log';
    }
}
