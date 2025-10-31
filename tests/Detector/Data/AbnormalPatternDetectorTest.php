<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Detector\Data;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;
use Tourze\AntiFraudBundle\Detector\Data\AbnormalPatternDetector;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\UserBehavior;
use Tourze\AntiFraudBundle\Tests\Contract\TestMetricsCollector;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(AbnormalPatternDetector::class)]
#[RunTestsInSeparateProcesses]
final class AbnormalPatternDetectorTest extends AbstractIntegrationTestCase
{
    private AbnormalPatternDetector $detector;

    private TestMetricsCollector $metricsCollector;

    private LoggerInterface $logger;

    protected function onSetUp(): void
    {
        // 创建 MetricsCollector 测试双对象
        $this->metricsCollector = new class implements TestMetricsCollector {
            /** @var array<string, int> */
            private array $requestCounts = [];

            private int $uniquePathsCount = 0;

            private int $errorCount = 0;

            public function getRequestCount(string $identifier, string $period): int
            {
                return $this->requestCounts[$identifier . '_' . $period] ?? 0;
            }

            public function setRequestCount(string $identifier, string $period, int $count): void
            {
                $this->requestCounts[$identifier . '_' . $period] = $count;
            }

            public function getUniquePathsCount(string $userId, string $timeWindow): int
            {
                return $this->uniquePathsCount;
            }

            public function setUniquePathsCount(int $count): void
            {
                $this->uniquePathsCount = $count;
            }

            public function getErrorCount(string $userId, string $timeWindow): int
            {
                return $this->errorCount;
            }

            public function setErrorCount(int $count): void
            {
                $this->errorCount = $count;
            }

            // 其他接口方法的空实现
            public function getUniqueUsersCount(string $identifier, string $type, int $seconds): int
            {
                return 0;
            }

            public function getLoginCount(string $userId): int
            {
                return 0;
            }

            public function incrementRequestCount(string $ip): void
            {
            }

            public function incrementLoginCount(string $userId): void
            {
            }

            public function getActionsPerSecond(string $sessionId): float
            {
                return 0.0;
            }

            public function getActionTimings(string $sessionId, int $limit): array
            {
                return [];
            }

            public function getUserLastScore(string $userId): ?float
            {
                // 在测试中，有时返回null以匹配接口设计
                return 'unknown_user' === $userId ? null : 0.0;
            }

            public function getUserDailyScoreIncrease(string $userId): float
            {
                return 0.0;
            }

            public function getActionCount(string $userId, string $action, string $timeWindow): int
            {
                return 0;
            }

            // TestMetricsCollector 特有方法
            public function setUserScore(string $userId, float $score): void
            {
            }

            public function setActionCount(string $userId, string $action, string $timeWindow, int $count): void
            {
            }
        };

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
        $this->detector = new AbnormalPatternDetector($this->metricsCollector, $this->logger);
    }

    public function testGetName(): void
    {
        $this->assertEquals('abnormal_pattern_detector', $this->detector->getName());
    }

    public function testDetectNormalPattern(): void
    {
        $this->metricsCollector->setRequestCount('user123', '1m', 10);
        $this->metricsCollector->setRequestCount('192.168.1.1', '1m', 10);
        $this->metricsCollector->setRequestCount('user123', '1h', 100);
        $this->metricsCollector->setRequestCount('user123', '30m', 5);
        $this->metricsCollector->setUniquePathsCount(5);
        $this->metricsCollector->setErrorCount(5);

        $userBehavior = new UserBehavior('user123', 'session123', '192.168.1.1', 'Mozilla/5.0', 'test_action');
        $context = new Context($userBehavior);
        $result = $this->detector->detect($context);

        $this->assertEquals(RiskLevel::LOW, $result->getRiskLevel());
    }

    public function testDetectHighRequestRate(): void
    {
        $this->metricsCollector->setRequestCount('user123', '1m', 100); // Very high
        $this->metricsCollector->setRequestCount('192.168.1.1', '1m', 100);
        $this->metricsCollector->setRequestCount('user123', '1h', 1000);
        $this->metricsCollector->setRequestCount('user123', '30m', 500);
        $this->metricsCollector->setUniquePathsCount(100); // Also high
        $this->metricsCollector->setErrorCount(200); // High error rate

        $userBehavior = new UserBehavior('user123', 'session123', '192.168.1.1', 'Mozilla/5.0', 'test_action');
        $context = new Context($userBehavior);
        $result = $this->detector->detect($context);

        $this->assertNotEquals(RiskLevel::LOW, $result->getRiskLevel());
    }

    public function testIsEnabled(): void
    {
        $this->assertTrue($this->detector->isEnabled());
    }
}
