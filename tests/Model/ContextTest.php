<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\UserBehavior;

/**
 * @internal
 */
#[CoversClass(Context::class)]
final class ContextTest extends TestCase
{
    protected function setUp(): void
    {
        // 无需特殊设置，使用父类的默认行为
    }

    private function createUserBehavior(): UserBehavior
    {
        return new UserBehavior(
            userId: 'user123',
            sessionId: 'session456',
            ip: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
            action: 'login'
        );
    }

    public function testConstructorWithUserBehavior(): void
    {
        $behavior = $this->createUserBehavior();
        $context = new Context($behavior);

        $this->assertSame($behavior, $context->getUserBehavior());
        $this->assertSame('user123', $context->getUserId());
        $this->assertSame('192.168.1.1', $context->getIp());
        $this->assertSame('/login', $context->getPath());
        $this->assertSame('GET', $context->getMethod());
        $this->assertSame('Mozilla/5.0', $context->getUserAgent());
    }

    public function testConstructorWithRequest(): void
    {
        $behavior = $this->createUserBehavior();
        $request = Request::create('/api/test', 'POST');
        $request->headers->set('User-Agent', 'TestAgent/1.0');

        $context = new Context($behavior, $request);

        $this->assertSame($behavior, $context->getUserBehavior());
        $this->assertSame($request, $context->getRequest());
        $this->assertSame('/api/test', $context->getPath());
        $this->assertSame('POST', $context->getMethod());
    }

    public function testSetAndGetAttributes(): void
    {
        $context = new Context($this->createUserBehavior());

        $context->setAttribute('ip_country', 'US');
        $context->setAttribute('is_proxy', true);
        $context->setAttribute('request_count', 5);

        $this->assertSame('US', $context->getAttribute('ip_country'));
        $this->assertTrue($context->getAttribute('is_proxy'));
        $this->assertSame(5, $context->getAttribute('request_count'));
        $this->assertNull($context->getAttribute('nonexistent'));
        $this->assertSame('default', $context->getAttribute('nonexistent', 'default'));
    }

    public function testHasAttribute(): void
    {
        $context = new Context($this->createUserBehavior());

        $this->assertFalse($context->hasAttribute('test'));

        $context->setAttribute('test', 'value');

        $this->assertTrue($context->hasAttribute('test'));
    }

    public function testGetAttributes(): void
    {
        $context = new Context($this->createUserBehavior());

        $context->setAttribute('attr1', 'value1');
        $context->setAttribute('attr2', 'value2');

        $attributes = $context->getAttributes();

        $this->assertCount(2, $attributes);
        $this->assertArrayHasKey('attr1', $attributes);
        $this->assertArrayHasKey('attr2', $attributes);
        $this->assertSame('value1', $attributes['attr1']);
        $this->assertSame('value2', $attributes['attr2']);
    }

    public function testIpCountryMethods(): void
    {
        $context = new Context($this->createUserBehavior());

        $this->assertNull($context->getIpCountry());

        $context->setAttribute('ip_country', 'CN');

        $this->assertSame('CN', $context->getIpCountry());
    }

    public function testIsProxyIpMethods(): void
    {
        $context = new Context($this->createUserBehavior());

        $this->assertFalse($context->isProxyIp());

        $context->setAttribute('is_proxy', true);

        $this->assertTrue($context->isProxyIp());
    }

    public function testFormSubmitTimeMethods(): void
    {
        $context = new Context($this->createUserBehavior());

        $this->assertNull($context->getFormSubmitTime());

        $context->setAttribute('form_submit_time', 2.5);

        $this->assertSame(2.5, $context->getFormSubmitTime());
    }

    public function testIsNewUserMethods(): void
    {
        $context = new Context($this->createUserBehavior());

        $this->assertFalse($context->isNewUser());

        $context->setAttribute('is_new_user', true);

        $this->assertTrue($context->isNewUser());
    }
}
