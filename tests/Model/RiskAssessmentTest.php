<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\RiskAssessment;

/**
 * @internal
 */
#[CoversClass(RiskAssessment::class)]
final class RiskAssessmentTest extends TestCase
{
    protected function setUp(): void
    {
        // 无需特殊设置，使用父类的默认行为
    }

    public function testConstructorAndGetters(): void
    {
        $assessment = new RiskAssessment(
            score: 75,
            level: RiskLevel::HIGH,
            detectionResults: [],
            details: ['reasons' => ['Too many login attempts', 'Suspicious IP']]
        );

        $this->assertSame(75, $assessment->getScore());
        $this->assertSame(RiskLevel::HIGH, $assessment->getLevel());
        $this->assertSame(['Too many login attempts', 'Suspicious IP'], $assessment->getReasons());
        $this->assertInstanceOf(\DateTimeImmutable::class, $assessment->getCreatedAt());
    }

    public function testCreateFromScore(): void
    {
        $assessment = RiskAssessment::createFromScore(85, ['High risk behavior detected']);

        $this->assertSame(85, $assessment->getScore());
        $this->assertSame(RiskLevel::HIGH, $assessment->getLevel());
        $this->assertSame(['High risk behavior detected'], $assessment->getReasons());
    }

    public function testAddReason(): void
    {
        $assessment = new RiskAssessment(50, RiskLevel::MEDIUM);

        $this->assertEmpty($assessment->getReasons());

        $assessment->addReason('Unusual location');
        $assessment->addReason('Multiple device login');

        $this->assertCount(2, $assessment->getReasons());
        $this->assertContains('Unusual location', $assessment->getReasons());
        $this->assertContains('Multiple device login', $assessment->getReasons());
    }

    public function testShouldTakeAction(): void
    {
        $lowRisk = new RiskAssessment(10, RiskLevel::LOW);
        $mediumRisk = new RiskAssessment(50, RiskLevel::MEDIUM);
        $highRisk = new RiskAssessment(80, RiskLevel::HIGH);
        $criticalRisk = new RiskAssessment(95, RiskLevel::CRITICAL);

        $this->assertFalse($lowRisk->shouldTakeAction());
        $this->assertFalse($mediumRisk->shouldTakeAction());
        $this->assertTrue($highRisk->shouldTakeAction());
        $this->assertTrue($criticalRisk->shouldTakeAction());
    }

    public function testIsHighRisk(): void
    {
        $lowRisk = new RiskAssessment(10, RiskLevel::LOW);
        $mediumRisk = new RiskAssessment(50, RiskLevel::MEDIUM);
        $highRisk = new RiskAssessment(80, RiskLevel::HIGH);
        $criticalRisk = new RiskAssessment(95, RiskLevel::CRITICAL);

        $this->assertFalse($lowRisk->isHighRisk());
        $this->assertFalse($mediumRisk->isHighRisk());
        $this->assertTrue($highRisk->isHighRisk());
        $this->assertTrue($criticalRisk->isHighRisk());
    }

    public function testToArray(): void
    {
        $assessment = new RiskAssessment(
            score: 75,
            level: RiskLevel::HIGH,
            detectionResults: [],
            details: ['reasons' => ['Reason 1', 'Reason 2']]
        );

        $array = $assessment->toArray();

        $this->assertArrayHasKey('score', $array);
        $this->assertArrayHasKey('level', $array);
        $this->assertArrayHasKey('reasons', $array);
        $this->assertArrayHasKey('createdTime', $array);
        $this->assertArrayHasKey('shouldTakeAction', $array);

        $this->assertSame(75, $array['score']);
        $this->assertSame('high', $array['level']);
        $this->assertSame(['Reason 1', 'Reason 2'], $array['reasons']);
        $this->assertTrue($array['shouldTakeAction']);
    }

    public function testMerge(): void
    {
        $assessment1 = new RiskAssessment(60, RiskLevel::MEDIUM, [], ['reasons' => ['Reason 1']]);
        $assessment2 = new RiskAssessment(80, RiskLevel::HIGH, [], ['reasons' => ['Reason 2']]);

        $merged = $assessment1->merge($assessment2);

        // Should take the higher score and level
        $this->assertSame(80, $merged->getScore());
        $this->assertSame(RiskLevel::HIGH, $merged->getLevel());

        // Should combine reasons
        $this->assertCount(2, $merged->getReasons());
        $this->assertContains('Reason 1', $merged->getReasons());
        $this->assertContains('Reason 2', $merged->getReasons());
    }
}
