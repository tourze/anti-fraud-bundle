<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Double;

use Tourze\AntiFraudBundle\Rule\Rule;

/**
 * Rule 类的测试双对象
 *
 * @internal
 */
final class TestRule
{
    private Rule $rule;

    public function __construct(string $name)
    {
        $this->rule = new Rule(
            name: $name,
            condition: 'true',
            actions: []
        );
    }

    public function getRule(): Rule
    {
        return $this->rule;
    }

    public function getName(): string
    {
        return $this->rule->getName();
    }

    public function getCondition(): string
    {
        return $this->rule->getCondition();
    }

    /**
     * @return array<string, mixed>
     */
    public function getActions(): array
    {
        return $this->rule->getActions();
    }

    public function getPriority(): int
    {
        return $this->rule->getPriority();
    }

    public function isEnabled(): bool
    {
        return $this->rule->isEnabled();
    }

    public function setEnabled(bool $enabled): void
    {
        $this->rule->setEnabled($enabled);
    }
}
