<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Detector\Operation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Tourze\AntiFraudBundle\Detector\Operation\AutomationDetector;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\UserBehavior;
use Tourze\AntiFraudBundle\Service\MetricsCollector;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(AutomationDetector::class)]
#[RunTestsInSeparateProcesses]
final class AutomationDetectorTest extends AbstractIntegrationTestCase
{
    private AutomationDetector $detector;

    private MetricsCollector&MockObject $metricsCollector;

    private LoggerInterface $logger;

    protected function onSetUp(): void
    {
        // 创建 MetricsCollector Mock 对象
        $this->metricsCollector = $this->createMock(MetricsCollector::class);

        // 创建 LoggerInterface 测试双对象
        $this->logger = new class implements LoggerInterface {
            public function emergency(\Stringable|string $message, array $context = []): void
            {
            }

            public function alert(\Stringable|string $message, array $context = []): void
            {
            }

            public function critical(\Stringable|string $message, array $context = []): void
            {
            }

            public function error(\Stringable|string $message, array $context = []): void
            {
            }

            public function warning(\Stringable|string $message, array $context = []): void
            {
            }

            public function notice(\Stringable|string $message, array $context = []): void
            {
            }

            public function info(\Stringable|string $message, array $context = []): void
            {
            }

            public function debug(\Stringable|string $message, array $context = []): void
            {
            }

            public function log($level, \Stringable|string $message, array $context = []): void
            {
            }
        };

        // @phpstan-ignore-next-line integrationTest.noDirectInstantiationOfCoveredClass - 需要使用测试双依赖验证行为
        $this->detector = new AutomationDetector($this->metricsCollector, $this->logger);
    }

    public function testGetName(): void
    {
        $this->assertEquals('automation_detector', $this->detector->getName());
    }

    public function testDetectNormalBehavior(): void
    {
        $this->metricsCollector->expects($this->any())
            ->method('getActionsPerSecond')
            ->willReturn(0.5)
        ;
        $this->metricsCollector->expects($this->any())
            ->method('getActionTimings')
            ->willReturn([
                1000, 2000, 1500, 3000, // Human-like intervals in ms
            ])
        ;

        $userBehavior = new UserBehavior('user123', 'session123', '192.168.1.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'test_action');
        $context = new Context($userBehavior);
        $context->setAttribute('action_intervals', [1000, 2000, 1500]);

        $result = $this->detector->detect($context);
        $this->assertEquals(RiskLevel::LOW, $result->getRiskLevel());
    }

    public function testDetectAutomatedBehavior(): void
    {
        $this->metricsCollector->expects($this->any())
            ->method('getActionsPerSecond')
            ->willReturn(10.0)
        ;
        $this->metricsCollector->expects($this->any())
            ->method('getActionTimings')
            ->willReturn([
                100, 100, 100, 100, // Machine-like consistent intervals
            ])
        ;

        $userBehavior = new UserBehavior('user123', 'session123', '192.168.1.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36', 'test_action');
        $context = new Context($userBehavior);
        $context->setAttribute('action_intervals', [100, 100, 100]);

        $result = $this->detector->detect($context);
        $this->assertNotEquals(RiskLevel::LOW, $result->getRiskLevel());
    }

    public function testDetectBotUserAgent(): void
    {
        $userBehavior = new UserBehavior('user123', 'session123', '192.168.1.1', 'bot/1.0', 'test_action');
        $context = new Context($userBehavior);

        $result = $this->detector->detect($context);
        $this->assertEquals(RiskLevel::MEDIUM, $result->getRiskLevel());
    }

    public function testIsEnabled(): void
    {
        $this->assertTrue($this->detector->isEnabled());
    }
}
