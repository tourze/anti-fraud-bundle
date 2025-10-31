<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum RiskLevel: string implements Itemable, Labelable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case CRITICAL = 'critical';

    public function getLabel(): string
    {
        return match ($this) {
            self::LOW => 'Low Risk',
            self::MEDIUM => 'Medium Risk',
            self::HIGH => 'High Risk',
            self::CRITICAL => 'Critical Risk',
        };
    }

    public function getScore(): int
    {
        return match ($this) {
            self::LOW => 0,
            self::MEDIUM => 30,
            self::HIGH => 70,
            self::CRITICAL => 90,
        };
    }

    public static function fromScore(int $score): self
    {
        return match (true) {
            $score < 30 => self::LOW,
            $score < 70 => self::MEDIUM,
            $score < 90 => self::HIGH,
            default => self::CRITICAL,
        };
    }

    public function isHigherThan(self $other): bool
    {
        return $this->getScore() > $other->getScore();
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::LOW => self::SUCCESS,
            self::MEDIUM => self::WARNING,
            self::HIGH => self::DANGER,
            self::CRITICAL => self::DANGER,
        };
    }
}
