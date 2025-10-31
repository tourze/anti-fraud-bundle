<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Model;

use Symfony\Component\HttpFoundation\Request;

class Context
{
    /** @var array<string, mixed> */
    private array $attributes = [];

    public function __construct(
        private UserBehavior $userBehavior,
        private ?Request $request = null,
    ) {
    }

    public function getUserBehavior(): UserBehavior
    {
        return $this->userBehavior;
    }

    public function getRequest(): ?Request
    {
        return $this->request;
    }

    public function getUserId(): string
    {
        return $this->userBehavior->getUserId();
    }

    public function getIp(): string
    {
        return $this->userBehavior->getIp();
    }

    public function getPath(): string
    {
        if (null !== $this->request) {
            return $this->request->getPathInfo();
        }

        // Default path based on action
        return '/' . $this->userBehavior->getAction();
    }

    public function getMethod(): string
    {
        if (null !== $this->request) {
            return $this->request->getMethod();
        }

        return 'GET';
    }

    public function getUserAgent(): string
    {
        return $this->userBehavior->getUserAgent();
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function hasAttribute(string $key): bool
    {
        return isset($this->attributes[$key]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    // Convenience methods for common attributes
    public function getIpCountry(): ?string
    {
        $value = $this->getAttribute('ip_country');

        return is_string($value) ? $value : null;
    }

    public function isProxyIp(): bool
    {
        return (bool) $this->getAttribute('is_proxy', false);
    }

    public function getFormSubmitTime(): ?float
    {
        $value = $this->getAttribute('form_submit_time');

        return is_float($value) || is_int($value) ? (float) $value : null;
    }

    public function isNewUser(): bool
    {
        return (bool) $this->getAttribute('is_new_user', false);
    }

    public function getSessionId(): string
    {
        return $this->userBehavior->getSessionId();
    }
}
