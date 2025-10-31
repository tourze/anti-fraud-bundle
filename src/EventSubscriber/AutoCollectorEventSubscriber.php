<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\EventSubscriber;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Tourze\AntiFraudBundle\Contract\Rule\RuleEngineInterface;
use Tourze\AntiFraudBundle\Contract\Service\DataCollectorInterface;

#[WithMonologChannel(channel: 'anti_fraud')]
class AutoCollectorEventSubscriber implements EventSubscriberInterface
{
    private bool $enabled;

    public function __construct(
        private readonly DataCollectorInterface $dataCollector,
        private readonly RuleEngineInterface $ruleEngine,
        private readonly LoggerInterface $logger,
    ) {
        // Control via environment variable, enabled by default
        $this->enabled = ($_ENV['ANTIFRAUD_ENABLED'] ?? 'true') !== 'false';
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 255],
            KernelEvents::RESPONSE => ['onKernelResponse', -255],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->enabled || !$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();

        // Skip static resources and admin interface
        if ($this->shouldSkip($request)) {
            return;
        }

        // Automatically collect data
        $context = $this->dataCollector->collect($request);

        try {
            // Real-time detection
            $result = $this->ruleEngine->evaluate($context);
        } catch (TableNotFoundException $e) {
            $this->logger->error('找不到相关表，跳过等待配置完成', [
                'exception' => $e,
            ]);
            return;
        }

        // If high risk detected, execute action immediately
        if ($result->shouldTakeAction()) {
            $response = $result->getAction()->execute($request);
            if (null !== $response) {
                $event->setResponse($response);
            }
        }

        // Store result in request attributes for later use
        $request->attributes->set('_antifraud_result', $result);
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        // Response handling - placeholder for future functionality
        // Could be used for adding security headers, logging, etc.
    }

    private function shouldSkip(Request $request): bool
    {
        $path = $request->getPathInfo();

        // Skip static resources
        if (1 === preg_match('/\.(js|css|png|jpg|gif|ico|woff|woff2|ttf)$/i', $path)) {
            return true;
        }

        // Skip admin interface (entire admin area to avoid interfering with security tests)
        if (str_starts_with($path, '/admin')) {
            return true;
        }

        // Skip health checks
        if ('/health' === $path || '/ping' === $path) {
            return true;
        }

        return false;
    }
}
