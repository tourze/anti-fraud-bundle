<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AntiFraudBundle\Service\MetricsCollectorImpl;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(MetricsCollectorImpl::class)]
#[RunTestsInSeparateProcesses]
final class MetricsCollectorImplTest extends AbstractIntegrationTestCase
{
    private MetricsCollectorImpl $collector;

    protected function onSetUp(): void
    {
        $this->collector = self::getService(MetricsCollectorImpl::class);
    }

    public function testGetRequestCountBasicFunctionality(): void
    {
        $ip = '192.168.1.1';
        $timeWindow = '5m';

        $count = $this->collector->getRequestCount($ip, $timeWindow);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testGetLoginCountBasicFunctionality(): void
    {
        $userId = 'user123';

        $count = $this->collector->getLoginCount($userId);

        $this->assertIsInt($count);
        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testIncrementRequestCount(): void
    {
        $ip = '192.168.1.1';

        $this->collector->incrementRequestCount($ip);
        $this->expectNotToPerformAssertions();
    }

    public function testIncrementLoginCount(): void
    {
        $userId = 'user123';

        $this->collector->incrementLoginCount($userId);
        $this->expectNotToPerformAssertions();
    }
}
