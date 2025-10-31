<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Detector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Tourze\AntiFraudBundle\Detector\IpRateLimitDetector;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\UserBehavior;
use Tourze\AntiFraudBundle\Service\MetricsCollector;
use Tourze\AntiFraudBundle\Tests\Contract\TestMetricsCollector;

/**
 * @internal
 */
#[CoversClass(IpRateLimitDetector::class)]
final class IpRateLimitDetectorTest extends TestCase
{
    private MetricsCollector&MockObject $metricsCollector;

    private IpRateLimitDetector $detector;

    private Context $context;

    protected function setUp(): void
    {
        // 创建 MetricsCollector Mock 对象
        $this->metricsCollector = $this->createMock(MetricsCollector::class);

        $this->detector = new IpRateLimitDetector($this->metricsCollector);

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

    public function testDetectorName(): void
    {
        $this->assertSame('ip-rate-limit', $this->detector->getName());
    }

    public function testDetectorIsEnabledByDefault(): void
    {
        $this->assertTrue($this->detector->isEnabled());
    }

    public function testDetectLowRiskWhenRequestCountBelowThreshold(): void
    {
        $this->metricsCollector->expects($this->any())
            ->method('getRequestCount')
            ->with('192.168.1.1', '1m')
            ->willReturn(10)
        ;

        $result = $this->detector->detect($this->context);

        $this->assertNotNull($result->getRiskAssessment());
        $this->assertSame(RiskLevel::LOW, $result->getRiskAssessment()->getRiskLevel());
        $this->assertStringContainsString('requests in last minute', $result->getRiskAssessment()->getReason());
        $this->assertSame(10, $result->getMetadata()['request_count_1m']);
        $this->assertSame('192.168.1.1', $result->getMetadata()['ip']);
    }

    public function testDetectHighRiskWhenRequestCountExceedsThreshold(): void
    {
        $this->metricsCollector->expects($this->any())
            ->method('getRequestCount')
            ->with('192.168.1.1', '1m')
            ->willReturn(65)
        ;

        $result = $this->detector->detect($this->context);

        $this->assertNotNull($result->getRiskAssessment());
        $this->assertSame(RiskLevel::HIGH, $result->getRiskAssessment()->getRiskLevel());
        $this->assertStringContainsString('High request rate detected', $result->getRiskAssessment()->getReason());
        $this->assertSame(65, $result->getMetadata()['request_count_1m']);
        $this->assertSame('192.168.1.1', $result->getMetadata()['ip']);
    }

    public function testDetectCriticalRiskWhenRequestCountExceedsCriticalThreshold(): void
    {
        $this->metricsCollector->expects($this->any())
            ->method('getRequestCount')
            ->with('192.168.1.1', '1m')
            ->willReturn(120)
        ;

        $result = $this->detector->detect($this->context);

        $this->assertNotNull($result->getRiskAssessment());
        $this->assertSame(RiskLevel::CRITICAL, $result->getRiskAssessment()->getRiskLevel());
        $this->assertStringContainsString('Critical request rate detected', $result->getRiskAssessment()->getReason());
        $this->assertSame(120, $result->getMetadata()['request_count_1m']);
        $this->assertSame('192.168.1.1', $result->getMetadata()['ip']);
    }

    public function testDetectWithDifferentIpAddresses(): void
    {
        $behavior1 = new UserBehavior(
            userId: 'test-user',
            sessionId: 'test-session',
            ip: '10.0.0.1',
            userAgent: 'Test Agent',
            action: 'test'
        );
        $context1 = new Context($behavior1, Request::create('/test'));

        $behavior2 = new UserBehavior(
            userId: 'test-user',
            sessionId: 'test-session',
            ip: '10.0.0.2',
            userAgent: 'Test Agent',
            action: 'test'
        );
        $context2 = new Context($behavior2, Request::create('/test'));

        $this->metricsCollector->expects($this->any())
            ->method('getRequestCount')
            ->willReturnMap([
                ['10.0.0.1', '1m', 10],
                ['10.0.0.2', '1m', 70],
            ])
        ;

        $result1 = $this->detector->detect($context1);
        $result2 = $this->detector->detect($context2);

        $this->assertNotNull($result1->getRiskAssessment());
        $this->assertNotNull($result2->getRiskAssessment());
        $this->assertSame(RiskLevel::LOW, $result1->getRiskAssessment()->getRiskLevel());
        $this->assertSame(RiskLevel::HIGH, $result2->getRiskAssessment()->getRiskLevel());
    }

    public function testDetectorCanBeDisabled(): void
    {
        // 创建一个禁用的检测器实例进行测试
        $detector = new IpRateLimitDetector($this->metricsCollector, false);

        $result = $detector->detect($this->context);

        $this->assertNotNull($result->getRiskAssessment());
        $this->assertSame(RiskLevel::LOW, $result->getRiskAssessment()->getRiskLevel());
        $this->assertSame('Detector disabled', $result->getRiskAssessment()->getReason());
    }

    public function testDetectorWithCustomThresholds(): void
    {
        // 创建一个自定义阈值的检测器实例进行测试
        $detector = new IpRateLimitDetector($this->metricsCollector, true, 50, 80);

        $this->metricsCollector->expects($this->any())
            ->method('getRequestCount')
            ->with('192.168.1.1', '1m')
            ->willReturn(60)
        ;

        $result = $detector->detect($this->context);

        $this->assertNotNull($result->getRiskAssessment());
        $this->assertSame(RiskLevel::HIGH, $result->getRiskAssessment()->getRiskLevel());
    }
}
