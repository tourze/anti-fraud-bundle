<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Rule\Action;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleAction implements ActionInterface
{
    public function __construct(
        private int $retryAfter = 60,
        private string $message = 'Too Many Requests',
        private ?int $rateLimit = null,
        private ?int $rateLimitRemaining = null,
        private ?int $rateLimitReset = null,
    ) {
    }

    public function execute(Request $request): ?Response
    {
        $headers = [
            'Retry-After' => (string) $this->retryAfter,
        ];

        if (null !== $this->rateLimit) {
            $headers['X-RateLimit-Limit'] = (string) $this->rateLimit;
        }

        if (null !== $this->rateLimitRemaining) {
            $headers['X-RateLimit-Remaining'] = (string) $this->rateLimitRemaining;
        }

        if (null !== $this->rateLimitReset) {
            $headers['X-RateLimit-Reset'] = (string) $this->rateLimitReset;
        }

        return new Response(
            $this->message,
            429,
            $headers
        );
    }

    public function getName(): string
    {
        return 'throttle';
    }
}
