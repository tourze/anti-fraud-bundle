<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\AntiFraudBundle\Entity\RiskProfile;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Repository\RiskProfileRepository;
use Tourze\AntiFraudBundle\Service\RiskLevelCalculator;
use Tourze\AntiFraudBundle\Service\RiskProfileService;

/**
 * @internal
 */
#[CoversClass(RiskProfileService::class)]
final class RiskProfileServiceTest extends TestCase
{
    private RiskProfileService $service;

    private RiskProfileRepository $repository;

    private RiskLevelCalculator $calculator;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(RiskProfileRepository::class);
        $this->calculator = new RiskLevelCalculator();
        $this->service = new RiskProfileService($this->repository, $this->calculator);
    }

    public function testUpdateProfileStatsWithHighRiskIncrementsCounters(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType('user');
        $profile->setIdentifierValue('test-user');

        $this->repository
            ->expects(self::once())
            ->method('findOrCreate')
            ->with('user', 'test-user')
            ->willReturn($profile)
        ;

        $this->repository
            ->expects(self::once())
            ->method('save')
            ->with(self::identicalTo($profile), true)
        ;

        $this->service->updateProfileStats('user', 'test-user', RiskLevel::HIGH, 'block');

        self::assertSame(1, $profile->getTotalDetections());
        self::assertSame(1, $profile->getHighRiskDetections());
        self::assertSame(1, $profile->getBlockedActions());
        self::assertNotNull($profile->getLastDetectionAt());
        self::assertNotNull($profile->getLastHighRiskAt());
    }

    public function testUpdateProfileStatsWithMediumRiskIncrementsCorrectCounters(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType('ip');
        $profile->setIdentifierValue('192.168.1.1');

        $this->repository
            ->expects(self::once())
            ->method('findOrCreate')
            ->with('ip', '192.168.1.1')
            ->willReturn($profile)
        ;

        $this->repository
            ->expects(self::once())
            ->method('save')
            ->with(self::identicalTo($profile), true)
        ;

        $this->service->updateProfileStats('ip', '192.168.1.1', RiskLevel::MEDIUM, 'throttle');

        self::assertSame(1, $profile->getTotalDetections());
        self::assertSame(1, $profile->getMediumRiskDetections());
        self::assertSame(1, $profile->getThrottledActions());
        self::assertSame(0, $profile->getHighRiskDetections());
        self::assertSame(0, $profile->getBlockedActions());
    }

    public function testUpdateProfileStatsWithLowRiskIncrementsLowRiskCounters(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType('session');
        $profile->setIdentifierValue('session123');

        $this->repository
            ->expects(self::once())
            ->method('findOrCreate')
            ->with('session', 'session123')
            ->willReturn($profile)
        ;

        $this->repository
            ->expects(self::once())
            ->method('save')
            ->with(self::identicalTo($profile), true)
        ;

        $this->service->updateProfileStats('session', 'session123', RiskLevel::LOW);

        self::assertSame(1, $profile->getTotalDetections());
        self::assertSame(1, $profile->getLowRiskDetections());
        self::assertSame(0, $profile->getBlockedActions());
        self::assertSame(0, $profile->getThrottledActions());
    }

    public function testUpdateProfileStatsCalculatesRiskLevel(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType('user');
        $profile->setIdentifierValue('test-user');

        // Simulate multiple high-risk detections
        for ($i = 0; $i < 5; ++$i) {
            $profile->incrementTotalDetections();
            $profile->incrementHighRiskDetections();
        }

        $this->repository
            ->expects(self::once())
            ->method('findOrCreate')
            ->willReturn($profile)
        ;

        $this->repository
            ->expects(self::once())
            ->method('save')
            ->with(self::callback(function (RiskProfile $savedProfile) {
                // Should calculate HIGH risk level based on high risk ratio
                return RiskLevel::HIGH === $savedProfile->getRiskLevel();
            }), true)
        ;

        $this->service->updateProfileStats('user', 'test-user', RiskLevel::HIGH, 'block');
    }
}
