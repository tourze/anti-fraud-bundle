<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Detector\Account;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Psr\Log\LoggerInterface;
use Tourze\AntiFraudBundle\Detector\Account\ProxyDetector;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\UserBehavior;
use Tourze\AntiFraudBundle\Rule\Action\LogAction;
use Tourze\AntiFraudBundle\Rule\Action\ThrottleAction;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ProxyDetector::class)]
#[RunTestsInSeparateProcesses]
final class ProxyDetectorTest extends AbstractIntegrationTestCase
{
    private ProxyDetector $detector;

    private LoggerInterface $logger;

    protected function onSetUp(): void
    {
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
        $this->detector = new ProxyDetector($this->logger);
    }

    public function testGetName(): void
    {
        $this->assertEquals('proxy_detector', $this->detector->getName());
    }

    public function testDetectLowRisk(): void
    {
        $userBehavior = new UserBehavior(
            'user123',
            'session123',
            '192.168.1.1',
            'Mozilla/5.0',
            'test_action'
        );
        $context = new Context($userBehavior);

        $result = $this->detector->detect($context);

        $this->assertEquals(RiskLevel::LOW, $result->getRiskLevel());
        $this->assertInstanceOf(LogAction::class, $result->getAction());
    }

    public function testDetectHighRiskWithProxyHeaders(): void
    {
        $userBehavior = new UserBehavior(
            'user123',
            'session123',
            '192.168.1.1',
            'Mozilla/5.0',
            'test_action'
        );
        $context = new Context($userBehavior);
        $context->setAttribute('headers', [
            'HTTP_X_FORWARDED_FOR' => '10.0.0.1',
            'HTTP_VIA' => 'proxy.example.com',
        ]);

        $result = $this->detector->detect($context);

        $this->assertNotEquals(RiskLevel::LOW, $result->getRiskLevel());
        $this->assertInstanceOf(ThrottleAction::class, $result->getAction());
    }

    public function testDetectWithSuspiciousPort(): void
    {
        $userBehavior = new UserBehavior(
            'user123',
            'session123',
            '192.168.1.1',
            'Mozilla/5.0',
            'test_action'
        );
        $context = new Context($userBehavior);
        $context->setAttribute('remote_port', 8080);

        $result = $this->detector->detect($context);

        $this->assertNotEquals(RiskLevel::LOW, $result->getRiskLevel());
    }

    public function testIsEnabled(): void
    {
        $this->assertTrue($this->detector->isEnabled());
    }
}
