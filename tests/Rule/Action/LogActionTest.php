<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Rule\Action;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Tourze\AntiFraudBundle\Rule\Action\LogAction;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(LogAction::class)]
#[RunTestsInSeparateProcesses]
final class LogActionTest extends AbstractIntegrationTestCase
{
    private LogAction $action;

    protected function onSetUp(): void
    {
        $this->action = self::getService(LogAction::class);
    }

    public function testServiceIsAvailable(): void
    {
        $this->assertInstanceOf(LogAction::class, $this->action);
    }

    public function testExecuteReturnsNull(): void
    {
        $request = Request::create('/test');
        $result = $this->action->execute($request);

        $this->assertNull($result);
    }

    public function testGetName(): void
    {
        $this->assertSame('log', $this->action->getName());
    }
}
