<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Rule\Action;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockAction implements ActionInterface
{
    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        private string $message = 'Access denied',
        private int $statusCode = 403,
        private array $headers = [],
    ) {
    }

    public function execute(Request $request): ?Response
    {
        return new Response(
            $this->message,
            $this->statusCode,
            $this->headers
        );
    }

    public function getName(): string
    {
        return 'block';
    }
}
