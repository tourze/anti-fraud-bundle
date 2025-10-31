<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Repository\DetectionLogRepository;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;

#[ORM\Entity(repositoryClass: DetectionLogRepository::class)]
#[ORM\Table(name: 'antifraud_detection_logs', options: ['comment' => '反欺诈检测日志表'])]
class DetectionLog implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '用户ID'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $userId;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '会话ID'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $sessionId;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 45, options: ['comment' => 'IP地址'])]
    #[Assert\NotBlank]
    #[Assert\Ip]
    #[Assert\Length(max: 45)]
    private string $ipAddress;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '用户代理'])]
    #[Assert\Length(max: 65535)]
    private ?string $userAgent = null;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '操作行为'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $action;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 20, enumType: RiskLevel::class, options: ['comment' => '风险等级'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [RiskLevel::class, 'cases'])]
    private RiskLevel $riskLevel;

    #[ORM\Column(type: Types::FLOAT, options: ['comment' => '风险分数'])]
    #[Assert\Range(min: 0, max: 1)]
    private float $riskScore;

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '匹配的规则'])]
    #[Assert\Type(type: 'array')]
    private array $matchedRules = [];

    /** @var array<string, mixed> */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '检测详情'])]
    #[Assert\Type(type: 'array')]
    private array $detectionDetails = [];

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '采取的行动'])]
    #[Assert\Length(max: 255)]
    private ?string $actionTaken = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '行动详情'])]
    #[Assert\Type(type: 'array')]
    private ?array $actionDetails = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '请求路径'])]
    #[Assert\Length(max: 255)]
    private ?string $requestPath = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true, options: ['comment' => '请求方法'])]
    #[Assert\Length(max: 10)]
    private ?string $requestMethod = null;

    /** @var array<string, mixed>|null */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '请求头'])]
    #[Assert\Type(type: 'array')]
    private ?array $requestHeaders = null;

    #[ORM\Column(type: Types::STRING, length: 2, nullable: true, options: ['comment' => '国家代码'])]
    #[Assert\Length(max: 2)]
    private ?string $countryCode = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否代理'])]
    #[Assert\Type(type: 'bool')]
    private bool $isProxy = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否机器人'])]
    #[Assert\Type(type: 'bool')]
    private bool $isBot = false;

    #[ORM\Column(type: Types::INTEGER, nullable: true, options: ['comment' => '响应时间'])]
    #[Assert\Type(type: 'int')]
    #[Assert\PositiveOrZero]
    private ?int $responseTime = null;

    #[IndexColumn]
    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '创建时间'])]
    #[Assert\Type(type: '\DateTimeImmutable')]
    private \DateTimeImmutable $createdTime;

    public function __construct()
    {
        $this->createdTime = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): void
    {
        $this->userId = $userId;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress;
    }

    public function setIpAddress(string $ipAddress): void
    {
        $this->ipAddress = $ipAddress;
    }

    public function getUserAgent(): ?string
    {
        return $this->userAgent;
    }

    public function setUserAgent(?string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function getAction(): string
    {
        return $this->action;
    }

    public function setAction(string $action): void
    {
        $this->action = $action;
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

    /**
     * @return array<string, mixed>
     * @phpstan-return array<string, mixed>
     */
    public function getMatchedRules(): array
    {
        return $this->matchedRules;
    }

    /**
     * @param array<string, mixed> $matchedRules
     */
    public function setMatchedRules(array $matchedRules): void
    {
        $this->matchedRules = $matchedRules;
    }

    /**
     * @return array<string, mixed>
     * @phpstan-return array<string, mixed>
     */
    public function getDetectionDetails(): array
    {
        return $this->detectionDetails;
    }

    /**
     * @param array<string, mixed> $detectionDetails
     */
    public function setDetectionDetails(array $detectionDetails): void
    {
        $this->detectionDetails = $detectionDetails;
    }

    public function getActionTaken(): ?string
    {
        return $this->actionTaken;
    }

    public function setActionTaken(?string $actionTaken): void
    {
        $this->actionTaken = $actionTaken;
    }

    /**
     * @return array<string, mixed>|null
     * @phpstan-return array<string, mixed>|null
     */
    public function getActionDetails(): ?array
    {
        return $this->actionDetails;
    }

    /**
     * @param array<string, mixed>|null $actionDetails
     */
    public function setActionDetails(?array $actionDetails): void
    {
        $this->actionDetails = $actionDetails;
    }

    public function getRequestPath(): ?string
    {
        return $this->requestPath;
    }

    public function setRequestPath(?string $requestPath): void
    {
        $this->requestPath = $requestPath;
    }

    public function getRequestMethod(): ?string
    {
        return $this->requestMethod;
    }

    public function setRequestMethod(?string $requestMethod): void
    {
        $this->requestMethod = $requestMethod;
    }

    /**
     * @return array<string, mixed>|null
     * @phpstan-return array<string, mixed>|null
     */
    public function getRequestHeaders(): ?array
    {
        return $this->requestHeaders;
    }

    /**
     * @param array<string, mixed>|null $requestHeaders
     */
    public function setRequestHeaders(?array $requestHeaders): void
    {
        $this->requestHeaders = $requestHeaders;
    }

    public function getCountryCode(): ?string
    {
        return $this->countryCode;
    }

    public function setCountryCode(?string $countryCode): void
    {
        $this->countryCode = $countryCode;
    }

    public function isProxy(): bool
    {
        return $this->isProxy;
    }

    public function setIsProxy(bool $isProxy): void
    {
        $this->isProxy = $isProxy;
    }

    public function isBot(): bool
    {
        return $this->isBot;
    }

    public function setIsBot(bool $isBot): void
    {
        $this->isBot = $isBot;
    }

    public function getResponseTime(): ?int
    {
        return $this->responseTime;
    }

    public function setResponseTime(?int $responseTime): void
    {
        $this->responseTime = $responseTime;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdTime;
    }

    public function __toString(): string
    {
        return sprintf(
            'DetectionLog #%d [%s] User: %s, IP: %s, Risk: %s (%.2f)',
            $this->id ?? 0,
            $this->createdTime->format('Y-m-d H:i:s'),
            $this->userId,
            $this->ipAddress,
            $this->riskLevel->value,
            $this->riskScore
        );
    }
}
