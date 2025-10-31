<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Detector\Account;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AntiFraudBundle\Detector\Account\MultiAccountDetector;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\UserBehavior;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(MultiAccountDetector::class)]
#[RunTestsInSeparateProcesses]
final class MultiAccountDetectorTest extends AbstractIntegrationTestCase
{
    private MultiAccountDetector $detector;

    protected function onSetUp(): void
    {
        // 从容器中获取服务实例（符合集成测试规范）
        $this->detector = self::getService(MultiAccountDetector::class);
    }

    public function testDetectBasicFunctionality(): void
    {
        $behavior = new UserBehavior(
            userId: 'user123',
            sessionId: 'session123',
            ip: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
            action: 'login'
        );
        $context = new Context($behavior);

        $result = $this->detector->detect($context);

        $this->assertInstanceOf(RiskLevel::class, $result->getRiskLevel());
        $this->assertIsArray($result->getDetails());
    }

    public function testGetName(): void
    {
        $this->assertEquals('multi_account_detector', $this->detector->getName());
    }

    public function testIsEnabled(): void
    {
        $this->assertTrue($this->detector->isEnabled());
    }
}
