<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(RiskLevel::class)]
#[RunTestsInSeparateProcesses]
final class RiskLevelTest extends AbstractEnumTestCase
{
    public function testRiskLevelValues(): void
    {
        $this->assertSame('low', RiskLevel::LOW->value);
        $this->assertSame('medium', RiskLevel::MEDIUM->value);
        $this->assertSame('high', RiskLevel::HIGH->value);
        $this->assertSame('critical', RiskLevel::CRITICAL->value);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('Low Risk', RiskLevel::LOW->getLabel());
        $this->assertSame('Medium Risk', RiskLevel::MEDIUM->getLabel());
        $this->assertSame('High Risk', RiskLevel::HIGH->getLabel());
        $this->assertSame('Critical Risk', RiskLevel::CRITICAL->getLabel());
    }

    public function testGetScore(): void
    {
        $this->assertSame(0, RiskLevel::LOW->getScore());
        $this->assertSame(30, RiskLevel::MEDIUM->getScore());
        $this->assertSame(70, RiskLevel::HIGH->getScore());
        $this->assertSame(90, RiskLevel::CRITICAL->getScore());
    }

    public function testFromScore(): void
    {
        $this->assertSame(RiskLevel::LOW, RiskLevel::fromScore(0));
        $this->assertSame(RiskLevel::LOW, RiskLevel::fromScore(29));
        $this->assertSame(RiskLevel::MEDIUM, RiskLevel::fromScore(30));
        $this->assertSame(RiskLevel::MEDIUM, RiskLevel::fromScore(69));
        $this->assertSame(RiskLevel::HIGH, RiskLevel::fromScore(70));
        $this->assertSame(RiskLevel::HIGH, RiskLevel::fromScore(89));
        $this->assertSame(RiskLevel::CRITICAL, RiskLevel::fromScore(90));
        $this->assertSame(RiskLevel::CRITICAL, RiskLevel::fromScore(100));
    }

    public function testIsHigherThan(): void
    {
        $this->assertTrue(RiskLevel::CRITICAL->isHigherThan(RiskLevel::HIGH));
        $this->assertTrue(RiskLevel::HIGH->isHigherThan(RiskLevel::MEDIUM));
        $this->assertTrue(RiskLevel::MEDIUM->isHigherThan(RiskLevel::LOW));

        $this->assertFalse(RiskLevel::LOW->isHigherThan(RiskLevel::MEDIUM));
        $this->assertFalse(RiskLevel::MEDIUM->isHigherThan(RiskLevel::HIGH));
        $this->assertFalse(RiskLevel::HIGH->isHigherThan(RiskLevel::CRITICAL));

        $this->assertFalse(RiskLevel::MEDIUM->isHigherThan(RiskLevel::MEDIUM));
    }

    public function testGetBadge(): void
    {
        $this->assertSame('success', RiskLevel::LOW->getBadge());
        $this->assertSame('warning', RiskLevel::MEDIUM->getBadge());
        $this->assertSame('danger', RiskLevel::HIGH->getBadge());
        $this->assertSame('danger', RiskLevel::CRITICAL->getBadge());
    }

    public function testToArray(): void
    {
        $expected = [
            'value' => 'low',
            'label' => 'Low Risk',
        ];

        $this->assertSame($expected, RiskLevel::LOW->toArray());

        // Test another enum value
        $expectedMedium = [
            'value' => 'medium',
            'label' => 'Medium Risk',
        ];
        $this->assertSame($expectedMedium, RiskLevel::MEDIUM->toArray());
    }
}
