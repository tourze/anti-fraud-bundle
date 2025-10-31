<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Rule;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tourze\AntiFraudBundle\Rule\Rule;

/**
 * @internal
 */
#[CoversClass(Rule::class)]
final class RuleTest extends TestCase
{
    protected function setUp(): void
    {
        // 无需特殊设置，使用父类的默认行为
    }

    public function testConstructorAndGetters(): void
    {
        $actions = ['type' => 'block', 'message' => 'Access denied'];

        $rule = new Rule(
            name: 'test_rule',
            condition: 'request.path = "/login"',
            actions: $actions,
            priority: 100,
            enabled: true
        );

        $this->assertSame('test_rule', $rule->getName());
        $this->assertSame('request.path = "/login"', $rule->getCondition());
        $this->assertSame($actions, $rule->getActions());
        $this->assertSame(100, $rule->getPriority());
        $this->assertTrue($rule->isEnabled());
    }

    public function testDefaultValues(): void
    {
        $actions = ['type' => 'log', 'level' => 'info'];

        $rule = new Rule(
            name: 'test_rule',
            condition: 'request.path = "/login"',
            actions: $actions
        );

        $this->assertSame(50, $rule->getPriority()); // Default priority
        $this->assertTrue($rule->isEnabled()); // Default enabled
    }

    public function testIsEnabled(): void
    {
        $actions = ['type' => 'throttle', 'delay' => 60];

        $rule = new Rule(
            name: 'test_rule',
            condition: 'request.path = "/login"',
            actions: $actions
        );

        $this->assertTrue($rule->isEnabled()); // Default enabled

        $rule->setEnabled(false);
        $this->assertFalse($rule->isEnabled());

        $rule->setEnabled(true);
        $this->assertTrue($rule->isEnabled());
    }
}
