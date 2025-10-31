<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Detector\Data;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Tourze\AntiFraudBundle\Detector\Data\ScoreManipulationDetector;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\UserBehavior;
use Tourze\AntiFraudBundle\Service\MetricsCollector;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ScoreManipulationDetector::class)]
#[RunTestsInSeparateProcesses]
final class ScoreManipulationDetectorTest extends AbstractIntegrationTestCase
{
    private ScoreManipulationDetector $detector;

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
        $this->detector = new ScoreManipulationDetector($this->metricsCollector, $this->logger);
    }

    public function testGetName(): void
    {
        $this->assertEquals('score_manipulation_detector', $this->detector->getName());
    }

    public function testDetectNormalScoreIncrement(): void
    {
        $this->metricsCollector->expects($this->any())
            ->method('getUserLastScore')
            ->willReturn(130.0)
        ;
        $this->metricsCollector->expects($this->any())
            ->method('getUserDailyScoreIncrease')
            ->willReturn(30.0)
        ;
        $this->metricsCollector->expects($this->any())
            ->method('getActionCount')
            ->willReturn(3)
        ;

        $userBehavior = new UserBehavior('user123', 'session123', '192.168.1.1', 'Mozilla/5.0', 'test_action');
        $context = new Context($userBehavior);
        $context->setAttribute('action', 'update_score');
        $context->setAttribute('score_change', 10);
        $context->setAttribute('current_score', 140);
        $context->setAttribute('score_data', ['current' => 140, 'previous' => 130]);

        $result = $this->detector->detect($context);
        $this->assertEquals(RiskLevel::LOW, $result->getRiskLevel());
    }

    public function testDetectSuspiciousScorePattern(): void
    {
        $this->metricsCollector->expects($this->any())
            ->method('getUserLastScore')
            ->willReturn(100.0)
        ;
        $this->metricsCollector->expects($this->any())
            ->method('getUserDailyScoreIncrease')
            ->willReturn(50.0)
        ;
        $this->metricsCollector->expects($this->any())
            ->method('getActionCount')
            ->willReturn(5)
        ;

        $userBehavior = new UserBehavior('user123', 'session123', '192.168.1.1', 'Mozilla/5.0', 'test_action');
        $context = new Context($userBehavior);
        $context->setAttribute('action', 'update_score');
        $context->setAttribute('score_change', 900);
        $context->setAttribute('current_score', 1000);
        $context->setAttribute('score_data', ['current_score' => 1000, 'previous_score' => 100]);

        $result = $this->detector->detect($context);
        $this->assertNotEquals(RiskLevel::LOW, $result->getRiskLevel());
    }

    public function testIsEnabled(): void
    {
        $this->assertTrue($this->detector->isEnabled());
    }
}
