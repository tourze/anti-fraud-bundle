<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Model;

class UserBehavior
{
    private \DateTimeImmutable $timestamp;

    /** @var array<string, mixed> */
    private array $metadata = [];

    public function __construct(
        private string $userId,
        private string $sessionId,
        private string $ip,
        private string $userAgent,
        private string $action,
    ) {
        $this->timestamp = new \DateTimeImmutable();
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function getUserAgent(): string
    {
        return $this->userAgent;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function getTimestamp(): \DateTimeImmutable
    {
        return $this->timestamp;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function setMetadata(array $metadata): void
    {
        $this->metadata = $metadata;
    }

    public function addMetadata(string $key, mixed $value): void
    {
        $this->metadata[$key] = $value;
    }

    public function getMetadataValue(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'userId' => $this->userId,
            'sessionId' => $this->sessionId,
            'ip' => $this->ip,
            'userAgent' => $this->userAgent,
            'action' => $this->action,
            'timestamp' => $this->timestamp->format('c'),
            'metadata' => $this->metadata,
        ];
    }
}
