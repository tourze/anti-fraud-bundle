<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Repository\RuleRepository;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;

#[ORM\Entity(repositoryClass: RuleRepository::class)]
#[ORM\Table(name: 'antifraud_rules', options: ['comment' => '反欺诈规则表'])]
#[ORM\Index(columns: ['enabled', 'priority'], name: 'antifraud_rules_idx_enabled_priority')]
class Rule implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 255, unique: true, options: ['comment' => '规则名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '规则条件'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 65535)]
    private string $condition;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: RiskLevel::class, options: ['comment' => '风险等级'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [RiskLevel::class, 'cases'])]
    private RiskLevel $riskLevel;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '执行动作'])]
    #[Assert\NotNull]
    #[Assert\Type(type: 'array')]
    private array $actions = [];

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '优先级'])]
    #[Assert\Range(min: 0, max: 1000)]
    private int $priority = 50;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否终止后续规则'])]
    #[Assert\Type(type: 'bool')]
    private bool $terminal = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否启用'])]
    #[Assert\Type(type: 'bool')]
    private bool $enabled = true;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '规则描述'])]
    #[Assert\Length(max: 65535)]
    private ?string $description = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '创建时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private \DateTimeImmutable $createdTime;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '更新时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private \DateTimeImmutable $updatedTime;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '版本号'])]
    #[ORM\Version]
    private int $version = 1;

    public function __construct()
    {
        $this->priority = 50;
        $this->terminal = false;
        $this->enabled = true;
        $this->createdTime = new \DateTimeImmutable();
        $this->updatedTime = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCondition(): string
    {
        return $this->condition;
    }

    public function setCondition(string $condition): void
    {
        $this->condition = $condition;
    }

    public function getRiskLevel(): RiskLevel
    {
        return $this->riskLevel;
    }

    public function setRiskLevel(RiskLevel $riskLevel): void
    {
        $this->riskLevel = $riskLevel;
    }

    /**
     * @return array<string, mixed>
     * @phpstan-return array<string, mixed>
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * @param array<string, mixed> $actions
     */
    public function setActions(array $actions): void
    {
        $this->actions = $actions;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    public function isTerminal(): bool
    {
        return $this->terminal;
    }

    public function setTerminal(bool $terminal): void
    {
        $this->terminal = $terminal;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdTime;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedTime;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setUpdatedTime(\DateTimeImmutable $updatedTime): void
    {
        $this->updatedTime = $updatedTime;
    }

    public function __toString(): string
    {
        return sprintf(
            'Rule #%d [%s] Risk: %s, Priority: %d, %s',
            $this->id ?? 0,
            $this->name,
            $this->riskLevel->value,
            $this->priority,
            $this->enabled ? 'Enabled' : 'Disabled'
        );
    }
}
