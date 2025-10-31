<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Rule;

use Tourze\AntiFraudBundle\Entity\Rule as RuleEntity;

/**
 * 规则模型类
 * 这是一个独立于实体的类，用于处理规则逻辑
 */
class Rule
{
    private string $name;

    private string $condition;

    /** @var array<string, mixed> */
    private array $actions;

    private int $priority;

    private bool $enabled;

    /**
     * @param array<string, mixed> $actions
     */
    public function __construct(
        string $name,
        string $condition,
        array $actions = [],
        int $priority = 50,
        bool $enabled = true,
    ) {
        $this->name = $name;
        $this->condition = $condition;
        $this->actions = $actions;
        $this->priority = $priority;
        $this->enabled = $enabled;
    }

    public static function fromEntity(RuleEntity $entity): self
    {
        return new self(
            $entity->getName(),
            $entity->getCondition(),
            $entity->getActions(),
            $entity->getPriority(),
            $entity->isEnabled()
        );
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getCondition(): string
    {
        return $this->condition;
    }

    /**
     * @return array<string, mixed>
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }
}
