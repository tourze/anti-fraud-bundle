<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Service\DataCollectorImpl;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(DataCollectorImpl::class)]
#[RunTestsInSeparateProcesses]
final class DataCollectorImplTest extends AbstractIntegrationTestCase
{
    private DataCollectorImpl $collector;

    protected function onSetUp(): void
    {
        $this->collector = self::getService(DataCollectorImpl::class);
    }

    public function testCollectBasicRequestData(): void
    {
        $request = Request::create('/login', 'POST');
        $request->headers->set('User-Agent', 'Mozilla/5.0');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $context = $this->collector->collect($request);

        $this->assertInstanceOf(Context::class, $context);
        $this->assertSame('/login', $context->getPath());
        $this->assertSame('POST', $context->getMethod());
    }
}
