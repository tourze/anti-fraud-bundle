<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Rule\Action;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\AntiFraudBundle\Rule\Action\ThrottleAction;

/**
 * @internal
 */
#[CoversClass(ThrottleAction::class)]
final class ThrottleActionTest extends TestCase
{
    public function testExecuteReturns429Response(): void
    {
        $action = new ThrottleAction();
        $request = Request::create('/test');

        $response = $action->execute($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertNotNull($response);
        $this->assertSame(429, $response->getStatusCode());
        $this->assertSame('Too Many Requests', $response->getContent());
    }

    public function testExecuteWithDefaultRetryAfter(): void
    {
        $action = new ThrottleAction();
        $request = Request::create('/test');

        $response = $action->execute($request);

        $this->assertNotNull($response);
        $this->assertSame('60', $response->headers->get('Retry-After'));
    }

    public function testExecuteWithCustomRetryAfter(): void
    {
        $action = new ThrottleAction(120);
        $request = Request::create('/test');

        $response = $action->execute($request);

        $this->assertNotNull($response);
        $this->assertSame('120', $response->headers->get('Retry-After'));
    }

    public function testExecuteWithCustomMessage(): void
    {
        $action = new ThrottleAction(60, 'Rate limit exceeded');
        $request = Request::create('/test');

        $response = $action->execute($request);

        $this->assertNotNull($response);
        $this->assertSame('Rate limit exceeded', $response->getContent());
    }

    public function testGetName(): void
    {
        $action = new ThrottleAction();

        $this->assertSame('throttle', $action->getName());
    }

    public function testExecuteWithRateLimitHeaders(): void
    {
        $action = new ThrottleAction(60, 'Too Many Requests', 100, 0, 1704110400);
        $request = Request::create('/test');

        $response = $action->execute($request);

        $this->assertNotNull($response);
        $this->assertSame('100', $response->headers->get('X-RateLimit-Limit'));
        $this->assertSame('0', $response->headers->get('X-RateLimit-Remaining'));
        $this->assertSame('1704110400', $response->headers->get('X-RateLimit-Reset'));
    }
}
