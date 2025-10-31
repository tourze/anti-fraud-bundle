<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Detector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Tourze\AntiFraudBundle\Contract\DetectorInterface;
use Tourze\AntiFraudBundle\Detector\AbstractDetector;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\DetectionResult;
use Tourze\AntiFraudBundle\Model\UserBehavior;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(AbstractDetector::class)]
#[RunTestsInSeparateProcesses]
final class AbstractDetectorTest extends AbstractIntegrationTestCase
{
    private Context $context;

    protected function onSetUp(): void
    {
        $behavior = new UserBehavior(
            userId: 'test-user',
            sessionId: 'test-session',
            ip: '192.168.1.1',
            userAgent: 'Test Agent',
            action: 'test'
        );

        $request = Request::create('/test');
        $this->context = new Context($behavior, $request);
    }

    public function testAbstractDetectorImplementsInterface(): void
    {
        $detector = new TestDetector();

        $this->assertInstanceOf(DetectorInterface::class, $detector);
    }

    public function testAbstractDetectorGetName(): void
    {
        $detector = new TestDetector();

        $this->assertSame('test-detector', $detector->getName());
    }

    public function testAbstractDetectorIsEnabled(): void
    {
        $enabledDetector = new TestDetector();
        $this->assertTrue($enabledDetector->isEnabled());

        $reflection = new \ReflectionClass(TestDetector::class);
        $detector = $reflection->newInstanceWithoutConstructor();
        $reflection->getProperty('enabled')->setValue($detector, false);
        $this->assertFalse($detector->isEnabled());
    }

    public function testAbstractDetectorDetectWhenEnabled(): void
    {
        $detector = new TestDetector(true, RiskLevel::HIGH);

        $result = $detector->detect($this->context);

        $this->assertInstanceOf(DetectionResult::class, $result);
        $this->assertNotNull($result->getRiskAssessment());
        $this->assertSame(RiskLevel::HIGH, $result->getRiskAssessment()->getRiskLevel());
        $this->assertTrue($result->getMetadata()['test_triggered']);
    }

    public function testAbstractDetectorDetectWhenDisabled(): void
    {
        $detector = new TestDetector(true, RiskLevel::HIGH);

        $reflection = new \ReflectionClass($detector);
        $reflection->getProperty('enabled')->setValue($detector, false);

        $result = $detector->detect($this->context);

        $this->assertInstanceOf(DetectionResult::class, $result);
        $this->assertNotNull($result->getRiskAssessment());
        $this->assertSame(RiskLevel::LOW, $result->getRiskAssessment()->getRiskLevel());
        $this->assertSame('Detector disabled', $result->getRiskAssessment()->getReason());
    }

    public function testAbstractDetectorDetectNoRisk(): void
    {
        $detector = new TestDetector(false);

        $result = $detector->detect($this->context);

        $this->assertInstanceOf(DetectionResult::class, $result);
        $this->assertNotNull($result->getRiskAssessment());
        $this->assertSame(RiskLevel::LOW, $result->getRiskAssessment()->getRiskLevel());
        $this->assertFalse($result->getMetadata()['test_triggered']);
    }

    public function testAbstractDetectorCanBeConfiguredWithDifferentRiskLevels(): void
    {
        $criticalDetector = new TestDetector(true, RiskLevel::CRITICAL);
        $mediumDetector = new TestDetector(true, RiskLevel::MEDIUM);

        $criticalResult = $criticalDetector->detect($this->context);
        $mediumResult = $mediumDetector->detect($this->context);

        $this->assertNotNull($criticalResult->getRiskAssessment());
        $this->assertNotNull($mediumResult->getRiskAssessment());
        $this->assertSame(RiskLevel::CRITICAL, $criticalResult->getRiskAssessment()->getRiskLevel());
        $this->assertSame(RiskLevel::MEDIUM, $mediumResult->getRiskAssessment()->getRiskLevel());
    }
}
