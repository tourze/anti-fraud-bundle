<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Model;

use Tourze\AntiFraudBundle\Enum\RiskLevel;

class RiskAssessment
{
    private \DateTimeImmutable $createdTime;

    /**
     * @param array<string, mixed> $detectionResults
     * @param array<string, mixed> $details
     */
    public function __construct(
        private float $score,
        private RiskLevel $level,
        private array $detectionResults = [],
        private array $details = [],
    ) {
        $this->createdTime = new \DateTimeImmutable();
    }

    /**
     * @param array<string> $reasons
     */
    public static function createFromScore(float $score, array $reasons = []): self
    {
        return new self(
            score: $score,
            level: RiskLevel::fromScore((int) $score),
            detectionResults: [],
            details: ['reasons' => $reasons]
        );
    }

    public function getScore(): int
    {
        return (int) $this->score;
    }

    public function getRiskScore(): float
    {
        return $this->score;
    }

    public function getLevel(): RiskLevel
    {
        return $this->level;
    }

    public function getRiskLevel(): RiskLevel
    {
        return $this->level;
    }

    /**
     * @return array<string>
     */
    public function getReasons(): array
    {
        $reasons = $this->details['reasons'] ?? [];

        return is_array($reasons) ? array_filter($reasons, 'is_string') : [];
    }

    public function getReason(): string
    {
        return implode(', ', $this->getReasons());
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetectionResults(): array
    {
        return $this->detectionResults;
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->details;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdTime;
    }

    public function addReason(string $reason): void
    {
        if (!isset($this->details['reasons']) || !is_array($this->details['reasons'])) {
            $this->details['reasons'] = [];
        }
        $this->details['reasons'][] = $reason;
    }

    public function shouldTakeAction(): bool
    {
        return $this->level->isHigherThan(RiskLevel::MEDIUM);
    }

    public function isHighRisk(): bool
    {
        return RiskLevel::HIGH === $this->level || RiskLevel::CRITICAL === $this->level;
    }

    public function merge(self $other): self
    {
        // Take the higher score and level
        $useOtherValues = $other->getScore() > $this->score;
        $score = $useOtherValues ? $other->getScore() : $this->score;
        $level = $useOtherValues ? $other->getLevel() : $this->level;

        // Combine reasons
        $reasons = array_unique(array_merge($this->getReasons(), $other->getReasons()));

        // Merge detection results
        $detectionResults = array_merge($this->detectionResults, $other->getDetectionResults());

        // Merge details
        $details = array_merge($this->details, $other->getDetails());
        $details['reasons'] = array_values($reasons);

        return new self($score, $level, $detectionResults, $details);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'score' => (int) $this->score,
            'level' => $this->level->value,
            'reasons' => $this->getReasons(),
            'detectionResults' => array_map(fn ($r) => is_object($r) && method_exists($r, 'toArray') ? $r->toArray() : $r, $this->detectionResults),
            'details' => $this->details,
            'createdTime' => $this->createdTime->format('c'),
            'shouldTakeAction' => $this->shouldTakeAction(),
        ];
    }
}
