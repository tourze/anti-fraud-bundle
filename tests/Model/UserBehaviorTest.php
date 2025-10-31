<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tourze\AntiFraudBundle\Model\UserBehavior;

/**
 * @internal
 */
#[CoversClass(UserBehavior::class)]
final class UserBehaviorTest extends TestCase
{
    protected function setUp(): void
    {
    }

    public function testConstructorAndGetters(): void
    {
        $behavior = new UserBehavior(
            userId: 'user123',
            sessionId: 'session456',
            ip: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
            action: 'login'
        );

        $this->assertSame('user123', $behavior->getUserId());
        $this->assertSame('session456', $behavior->getSessionId());
        $this->assertSame('192.168.1.1', $behavior->getIp());
        $this->assertSame('Mozilla/5.0', $behavior->getUserAgent());
        $this->assertSame('login', $behavior->getAction());
        $this->assertInstanceOf(\DateTimeImmutable::class, $behavior->getTimestamp());
    }

    public function testSetAndGetMetadata(): void
    {
        $behavior = new UserBehavior(
            userId: 'user123',
            sessionId: 'session456',
            ip: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
            action: 'login'
        );

        $metadata = [
            'form_submit_time' => 2.5,
            'referer' => 'https://example.com',
            'method' => 'POST',
        ];

        $behavior->setMetadata($metadata);
        $this->assertSame($metadata, $behavior->getMetadata());
    }

    public function testAddMetadata(): void
    {
        $behavior = new UserBehavior(
            userId: 'user123',
            sessionId: 'session456',
            ip: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
            action: 'login'
        );

        $behavior->addMetadata('key1', 'value1');
        $behavior->addMetadata('key2', 'value2');

        $expected = [
            'key1' => 'value1',
            'key2' => 'value2',
        ];

        $this->assertSame($expected, $behavior->getMetadata());
    }

    public function testGetMetadataValue(): void
    {
        $behavior = new UserBehavior(
            userId: 'user123',
            sessionId: 'session456',
            ip: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
            action: 'login'
        );

        $behavior->setMetadata(['key1' => 'value1', 'key2' => 'value2']);

        $this->assertSame('value1', $behavior->getMetadataValue('key1'));
        $this->assertSame('value2', $behavior->getMetadataValue('key2'));
        $this->assertNull($behavior->getMetadataValue('nonexistent'));
        $this->assertSame('default', $behavior->getMetadataValue('nonexistent', 'default'));
    }

    public function testToArray(): void
    {
        $behavior = new UserBehavior(
            userId: 'user123',
            sessionId: 'session456',
            ip: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
            action: 'login'
        );

        $behavior->setMetadata(['form_submit_time' => 2.5]);

        $array = $behavior->toArray();

        $this->assertArrayHasKey('userId', $array);
        $this->assertArrayHasKey('sessionId', $array);
        $this->assertArrayHasKey('ip', $array);
        $this->assertArrayHasKey('userAgent', $array);
        $this->assertArrayHasKey('action', $array);
        $this->assertArrayHasKey('timestamp', $array);
        $this->assertArrayHasKey('metadata', $array);

        $this->assertSame('user123', $array['userId']);
        $this->assertSame('session456', $array['sessionId']);
        $this->assertSame('192.168.1.1', $array['ip']);
        $this->assertSame('Mozilla/5.0', $array['userAgent']);
        $this->assertSame('login', $array['action']);
        $this->assertSame(['form_submit_time' => 2.5], $array['metadata']);
    }
}
