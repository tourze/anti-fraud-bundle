<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AntiFraudBundle\Entity\RiskProfile;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(RiskProfile::class)]
final class RiskProfileTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType(RiskProfile::TYPE_USER);
        $profile->setIdentifierValue('test-user');

        return $profile;
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'riskLevel' => ['riskLevel', RiskLevel::HIGH];
        yield 'riskScore' => ['riskScore', 0.75];
        yield 'notes' => ['notes', 'Test notes'];
        yield 'metadata' => ['metadata', ['key' => 'value']];
    }

    public function testConstructorSetsDefaultValues(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType(RiskProfile::TYPE_USER);
        $profile->setIdentifierValue('user123');

        $this->assertEquals(RiskProfile::TYPE_USER, $profile->getIdentifierType());
        $this->assertEquals('user123', $profile->getIdentifierValue());
        $this->assertEquals(RiskLevel::LOW, $profile->getRiskLevel());
        $this->assertEquals(0.0, $profile->getRiskScore());
        $this->assertEquals(0, $profile->getTotalDetections());
        $this->assertEquals(0, $profile->getHighRiskDetections());
        $this->assertEquals(0, $profile->getMediumRiskDetections());
        $this->assertEquals(0, $profile->getLowRiskDetections());
        $this->assertEquals(0, $profile->getBlockedActions());
        $this->assertEquals(0, $profile->getThrottledActions());
        $this->assertEquals([], $profile->getRiskFactors());
        $this->assertEquals([], $profile->getBehaviorPatterns());
        $this->assertNull($profile->getLastHighRiskAt());
        $this->assertNull($profile->getLastDetectionAt());
        $this->assertFalse($profile->isWhitelisted());
        $this->assertFalse($profile->isBlacklisted());
        $this->assertNull($profile->getNotes());
        $this->assertNull($profile->getMetadata());
        $this->assertInstanceOf(\DateTimeImmutable::class, $profile->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $profile->getUpdatedAt());
        $this->assertEquals(1, $profile->getVersion());
    }

    public function testTypeConstants(): void
    {
        $this->assertEquals('user', RiskProfile::TYPE_USER);
        $this->assertEquals('ip', RiskProfile::TYPE_IP);
        $this->assertEquals('device', RiskProfile::TYPE_DEVICE);
        $this->assertEquals('session', RiskProfile::TYPE_SESSION);
    }

    public function testRiskLevelGetterAndSetter(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType(RiskProfile::TYPE_IP);
        $profile->setIdentifierValue('192.168.1.1');

        $profile->setRiskLevel(RiskLevel::HIGH);

        $this->assertEquals(RiskLevel::HIGH, $profile->getRiskLevel());
    }

    public function testRiskScoreGetterAndSetter(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType(RiskProfile::TYPE_IP);
        $profile->setIdentifierValue('192.168.1.1');

        $profile->setRiskScore(0.75);

        $this->assertEquals(0.75, $profile->getRiskScore());
    }

    public function testIncrementTotalDetections(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType(RiskProfile::TYPE_USER);
        $profile->setIdentifierValue('user123');

        $this->assertEquals(0, $profile->getTotalDetections());

        $profile->incrementTotalDetections();

        $this->assertEquals(1, $profile->getTotalDetections());
    }

    public function testIncrementHighRiskDetections(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType(RiskProfile::TYPE_USER);
        $profile->setIdentifierValue('user123');

        $this->assertEquals(0, $profile->getHighRiskDetections());

        $profile->incrementHighRiskDetections();

        $this->assertEquals(1, $profile->getHighRiskDetections());
    }

    public function testIncrementMediumRiskDetections(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType(RiskProfile::TYPE_USER);
        $profile->setIdentifierValue('user123');

        $this->assertEquals(0, $profile->getMediumRiskDetections());

        $profile->incrementMediumRiskDetections();

        $this->assertEquals(1, $profile->getMediumRiskDetections());
    }

    public function testIncrementLowRiskDetections(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType(RiskProfile::TYPE_USER);
        $profile->setIdentifierValue('user123');

        $this->assertEquals(0, $profile->getLowRiskDetections());

        $profile->incrementLowRiskDetections();

        $this->assertEquals(1, $profile->getLowRiskDetections());
    }

    public function testIncrementBlockedActions(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType(RiskProfile::TYPE_USER);
        $profile->setIdentifierValue('user123');

        $this->assertEquals(0, $profile->getBlockedActions());

        $profile->incrementBlockedActions();

        $this->assertEquals(1, $profile->getBlockedActions());
    }

    public function testIncrementThrottledActions(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType(RiskProfile::TYPE_USER);
        $profile->setIdentifierValue('user123');

        $this->assertEquals(0, $profile->getThrottledActions());

        $profile->incrementThrottledActions();

        $this->assertEquals(1, $profile->getThrottledActions());
    }

    public function testRiskFactorsGetterAndSetter(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType(RiskProfile::TYPE_USER);
        $profile->setIdentifierValue('user123');
        $riskFactors = ['factor1' => 'value1', 'factor2' => 'value2'];

        $profile->setRiskFactors($riskFactors);

        $this->assertEquals($riskFactors, $profile->getRiskFactors());
    }

    public function testAddRiskFactor(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType(RiskProfile::TYPE_USER);
        $profile->setIdentifierValue('user123');

        $profile->addRiskFactor('test_factor', 'test_value');

        $this->assertEquals(['test_factor' => 'test_value'], $profile->getRiskFactors());
    }

    public function testBehaviorPatternsGetterAndSetter(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType(RiskProfile::TYPE_USER);
        $profile->setIdentifierValue('user123');
        $patterns = ['pattern1' => 'data1'];

        $profile->setBehaviorPatterns($patterns);

        $this->assertEquals($patterns, $profile->getBehaviorPatterns());
    }

    public function testLastHighRiskAtGetterAndSetter(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType(RiskProfile::TYPE_USER);
        $profile->setIdentifierValue('user123');
        $time = new \DateTimeImmutable();

        $profile->setLastHighRiskAt($time);

        $this->assertEquals($time, $profile->getLastHighRiskAt());
    }

    public function testLastDetectionAtGetterAndSetter(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType(RiskProfile::TYPE_USER);
        $profile->setIdentifierValue('user123');
        $time = new \DateTimeImmutable();

        $profile->setLastDetectionAt($time);

        $this->assertEquals($time, $profile->getLastDetectionAt());
    }

    public function testIsWhitelistedGetterAndSetter(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType(RiskProfile::TYPE_USER);
        $profile->setIdentifierValue('user123');

        $profile->setIsWhitelisted(true);

        $this->assertTrue($profile->isWhitelisted());
    }

    public function testIsBlacklistedGetterAndSetter(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType(RiskProfile::TYPE_USER);
        $profile->setIdentifierValue('user123');

        $profile->setIsBlacklisted(true);

        $this->assertTrue($profile->isBlacklisted());
    }

    public function testNotesGetterAndSetter(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType(RiskProfile::TYPE_USER);
        $profile->setIdentifierValue('user123');
        $notes = 'Some notes about this user';

        $profile->setNotes($notes);

        $this->assertEquals($notes, $profile->getNotes());
    }

    public function testMetadataGetterAndSetter(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType(RiskProfile::TYPE_USER);
        $profile->setIdentifierValue('user123');
        $metadata = ['key1' => 'value1', 'key2' => 'value2'];

        $profile->setMetadata($metadata);

        $this->assertEquals($metadata, $profile->getMetadata());
    }

    public function testSetUpdatedTime(): void
    {
        $profile = new RiskProfile();
        $profile->setIdentifierType(RiskProfile::TYPE_USER);
        $profile->setIdentifierValue('user123');
        $originalTime = $profile->getUpdatedAt();

        usleep(1000); // 等待 1ms 确保时间差异
        $newTime = new \DateTimeImmutable();
        $profile->setUpdatedTime($newTime);

        $this->assertGreaterThan($originalTime, $profile->getUpdatedAt());
        $this->assertEquals($newTime, $profile->getUpdatedAt());
    }
}
