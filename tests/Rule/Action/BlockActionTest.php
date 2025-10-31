<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Rule\Action;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\AntiFraudBundle\Rule\Action\BlockAction;

/**
 * @internal
 */
#[CoversClass(BlockAction::class)]
final class BlockActionTest extends TestCase
{
    public function testExecuteReturns403Response(): void
    {
        $action = new BlockAction();
        $request = Request::create('/test');

        $response = $action->execute($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertNotNull($response);
        $this->assertSame(403, $response->getStatusCode());
        $this->assertSame('Access denied', $response->getContent());
    }

    public function testExecuteWithCustomMessage(): void
    {
        $customMessage = 'Too many login attempts';
        $action = new BlockAction($customMessage);
        $request = Request::create('/test');

        $response = $action->execute($request);

        $this->assertNotNull($response);
        $this->assertSame($customMessage, $response->getContent());
    }

    public function testExecuteWithCustomStatusCode(): void
    {
        $action = new BlockAction('Forbidden', 401);
        $request = Request::create('/test');

        $response = $action->execute($request);

        $this->assertNotNull($response);
        $this->assertSame(401, $response->getStatusCode());
        $this->assertSame('Forbidden', $response->getContent());
    }

    public function testGetName(): void
    {
        $action = new BlockAction();

        $this->assertSame('block', $action->getName());
    }

    public function testExecuteWithHeaders(): void
    {
        $headers = [
            'X-Block-Reason' => 'Suspicious activity',
            'X-Block-Time' => '2024-01-01 12:00:00',
        ];

        $action = new BlockAction('Access denied', 403, $headers);
        $request = Request::create('/test');

        $response = $action->execute($request);

        $this->assertNotNull($response);
        $this->assertSame('Suspicious activity', $response->headers->get('X-Block-Reason'));
        $this->assertSame('2024-01-01 12:00:00', $response->headers->get('X-Block-Time'));
    }
}
