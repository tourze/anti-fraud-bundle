<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tourze\AntiFraudBundle\Entity\RiskProfile;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Service\RiskLevelCalculator;

/**
 * @internal
 */
#[CoversClass(RiskLevelCalculator::class)]
final class RiskLevelCalculatorTest extends TestCase
{
    private RiskLevelCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new RiskLevelCalculator();
    }

    public function testBlacklistedProfileReturnsCritical(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType('user');
        $profile->setIdentifierValue('test-user');
        $profile->setIsBlacklisted(true);

        $result = $this->calculator->calculateRiskLevel($profile);

        self::assertSame(RiskLevel::CRITICAL, $result);
    }

    public function testWhitelistedProfileReturnsLow(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType('user');
        $profile->setIdentifierValue('test-user');
        $profile->setIsWhitelisted(true);

        $result = $this->calculator->calculateRiskLevel($profile);

        self::assertSame(RiskLevel::LOW, $result);
    }

    /**
     * @return array<string, array{highRiskDetections: int, totalDetections: int, blockedActions: int, expected: RiskLevel}>
     */
    public static function riskLevelDataProvider(): array
    {
        return [
            'high risk ratio over 50%' => [
                'highRiskDetections' => 6,
                'totalDetections' => 10,
                'blockedActions' => 0,
                'expected' => RiskLevel::HIGH,
            ],
            'blocked actions over 5' => [
                'highRiskDetections' => 1,
                'totalDetections' => 10,
                'blockedActions' => 6,
                'expected' => RiskLevel::HIGH,
            ],
            'medium risk ratio over 30%' => [
                'highRiskDetections' => 4,
                'totalDetections' => 10,
                'blockedActions' => 0,
                'expected' => RiskLevel::MEDIUM,
            ],
            'blocked actions over 2' => [
                'highRiskDetections' => 1,
                'totalDetections' => 10,
                'blockedActions' => 3,
                'expected' => RiskLevel::MEDIUM,
            ],
            'low risk default' => [
                'highRiskDetections' => 1,
                'totalDetections' => 10,
                'blockedActions' => 0,
                'expected' => RiskLevel::LOW,
            ],
        ];
    }

    #[DataProvider('riskLevelDataProvider')]
    public function testCalculateRiskLevelBasedOnHistory(int $highRiskDetections, int $totalDetections, int $blockedActions, RiskLevel $expected): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType('user');
        $profile->setIdentifierValue('test-user');

        // Simulate detection history
        for ($i = 0; $i < $highRiskDetections; ++$i) {
            $profile->incrementHighRiskDetections();
            $profile->incrementTotalDetections();
        }

        for ($i = $highRiskDetections; $i < $totalDetections; ++$i) {
            $profile->incrementLowRiskDetections();
            $profile->incrementTotalDetections();
        }

        for ($i = 0; $i < $blockedActions; ++$i) {
            $profile->incrementBlockedActions();
        }

        $result = $this->calculator->calculateRiskLevel($profile);

        self::assertSame($expected, $result);
    }
}
