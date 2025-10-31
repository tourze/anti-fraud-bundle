<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Repository\RiskProfileRepository;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;

#[ORM\Entity(repositoryClass: RiskProfileRepository::class)]
#[ORM\Table(name: 'antifraud_risk_profiles', options: ['comment' => '风险概况表'])]
#[ORM\UniqueConstraint(name: 'unique_identifier', columns: ['identifier_type', 'identifier_value'])]
#[ORM\Index(name: 'antifraud_risk_profiles_idx_identifier', columns: ['identifier_type', 'identifier_value'])]
class RiskProfile implements \Stringable
{
    public const TYPE_USER = 'user';
    public const TYPE_IP = 'ip';
    public const TYPE_DEVICE = 'device';
    public const TYPE_SESSION = 'session';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '标识类型'])]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: [self::TYPE_USER, self::TYPE_IP, self::TYPE_DEVICE, self::TYPE_SESSION])]
    #[Assert\Length(max: 50)]
    private string $identifierType;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '标识值'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $identifierValue;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 20, enumType: RiskLevel::class, options: ['comment' => '风险等级'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [RiskLevel::class, 'cases'])]
    private RiskLevel $riskLevel;

    #[ORM\Column(type: Types::FLOAT, options: ['comment' => '风险分数'])]
    #[Assert\Range(min: 0, max: 1)]
    private float $riskScore = 0.0;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '总检测次数'])]
    #[Assert\PositiveOrZero]
    private int $totalDetections = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '高风险检测次数'])]
    #[Assert\PositiveOrZero]
    private int $highRiskDetections = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '中风险检测次数'])]
    #[Assert\PositiveOrZero]
    private int $mediumRiskDetections = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '低风险检测次数'])]
    #[Assert\PositiveOrZero]
    private int $lowRiskDetections = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '阻止的操作次数'])]
    #[Assert\PositiveOrZero]
    private int $blockedActions = 0;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '限流的操作次数'])]
    #[Assert\PositiveOrZero]
    private int $throttledActions = 0;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '风险因素'])]
    #[Assert\Type(type: 'array')]
    private array $riskFactors = [];

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '行为模式'])]
    #[Assert\Type(type: 'array')]
    private array $behaviorPatterns = [];

    #[ORM\Column(name: 'last_high_risk_at', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后高风险时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $lastHighRiskTime = null;

    #[ORM\Column(name: 'last_detection_at', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '最后检测时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private ?\DateTimeImmutable $lastDetectionTime = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否白名单'])]
    #[Assert\Type(type: 'bool')]
    private bool $isWhitelisted = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否黑名单'])]
    #[Assert\Type(type: 'bool')]
    private bool $isBlacklisted = false;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '备注'])]
    #[Assert\Length(max: 255)]
    private ?string $notes = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '元数据'])]
    #[Assert\Type(type: 'array')]
    private ?array $metadata = null;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '创建时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private \DateTimeImmutable $createdTime;

    #[IndexColumn]
    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '更新时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private \DateTimeImmutable $updatedTime;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '版本号'])]
    #[ORM\Version]
    private int $version = 1;

    public function __construct()
    {
        $this->riskLevel = RiskLevel::LOW;
        $this->createdTime = new \DateTimeImmutable();
        $this->updatedTime = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdentifierType(): string
    {
        return $this->identifierType;
    }

    public function setIdentifierType(string $identifierType): void
    {
        $this->identifierType = $identifierType;
    }

    public function getIdentifierValue(): string
    {
        return $this->identifierValue;
    }

    public function setIdentifierValue(string $identifierValue): void
    {
        $this->identifierValue = $identifierValue;
    }

    public function getRiskLevel(): RiskLevel
    {
        return $this->riskLevel;
    }

    public function setRiskLevel(RiskLevel $riskLevel): void
    {
        $this->riskLevel = $riskLevel;
    }

    public function getRiskScore(): float
    {
        return $this->riskScore;
    }

    public function setRiskScore(float $riskScore): void
    {
        $this->riskScore = $riskScore;
    }

    public function getTotalDetections(): int
    {
        return $this->totalDetections;
    }

    public function incrementTotalDetections(): void
    {
        ++$this->totalDetections;
    }

    public function getHighRiskDetections(): int
    {
        return $this->highRiskDetections;
    }

    public function incrementHighRiskDetections(): void
    {
        ++$this->highRiskDetections;
    }

    public function getMediumRiskDetections(): int
    {
        return $this->mediumRiskDetections;
    }

    public function incrementMediumRiskDetections(): void
    {
        ++$this->mediumRiskDetections;
    }

    public function getLowRiskDetections(): int
    {
        return $this->lowRiskDetections;
    }

    public function incrementLowRiskDetections(): void
    {
        ++$this->lowRiskDetections;
    }

    public function getBlockedActions(): int
    {
        return $this->blockedActions;
    }

    public function incrementBlockedActions(): void
    {
        ++$this->blockedActions;
    }

    public function getThrottledActions(): int
    {
        return $this->throttledActions;
    }

    public function incrementThrottledActions(): void
    {
        ++$this->throttledActions;
    }

    /**
     * @return array<string, mixed>
     * @phpstan-return array<string, mixed>
     */
    public function getRiskFactors(): array
    {
        return $this->riskFactors;
    }

    /**
     * @param array<string, mixed> $riskFactors
     */
    public function setRiskFactors(array $riskFactors): void
    {
        $this->riskFactors = $riskFactors;
    }

    public function addRiskFactor(string $factor, mixed $value): void
    {
        $this->riskFactors[$factor] = $value;
    }

    /**
     * @return array<string, mixed>
     * @phpstan-return array<string, mixed>
     */
    public function getBehaviorPatterns(): array
    {
        return $this->behaviorPatterns;
    }

    /**
     * @param array<string, mixed> $behaviorPatterns
     */
    public function setBehaviorPatterns(array $behaviorPatterns): void
    {
        $this->behaviorPatterns = $behaviorPatterns;
    }

    public function getLastHighRiskAt(): ?\DateTimeImmutable
    {
        return $this->lastHighRiskTime;
    }

    public function setLastHighRiskAt(?\DateTimeImmutable $lastHighRiskAt): void
    {
        $this->lastHighRiskTime = $lastHighRiskAt;
    }

    public function getLastDetectionAt(): ?\DateTimeImmutable
    {
        return $this->lastDetectionTime;
    }

    public function setLastDetectionAt(?\DateTimeImmutable $lastDetectionAt): void
    {
        $this->lastDetectionTime = $lastDetectionAt;
    }

    public function isWhitelisted(): bool
    {
        return $this->isWhitelisted;
    }

    public function setIsWhitelisted(bool $isWhitelisted): void
    {
        $this->isWhitelisted = $isWhitelisted;
    }

    public function isBlacklisted(): bool
    {
        return $this->isBlacklisted;
    }

    public function setIsBlacklisted(bool $isBlacklisted): void
    {
        $this->isBlacklisted = $isBlacklisted;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): void
    {
        $this->notes = $notes;
    }

    /**
     * @return array<string, mixed>|null
     * @phpstan-return array<string, mixed>|null
     */
    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    /**
     * @param array<string, mixed>|null $metadata
     */
    public function setMetadata(?array $metadata): void
    {
        $this->metadata = $metadata;
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
            'RiskProfile #%d [%s:%s] Risk: %s (%.2f)',
            $this->id ?? 0,
            $this->identifierType,
            $this->identifierValue,
            $this->riskLevel->value,
            $this->riskScore
        );
    }
}
