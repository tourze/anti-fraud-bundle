<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\AntiFraudBundle\Entity\Rule;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Rule::class)]
final class RuleTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        $rule = new Rule();
        $rule->setName('test-rule');
        $rule->setCondition('condition');
        $rule->setRiskLevel(RiskLevel::LOW);

        return $rule;
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'name' => ['name', 'New Rule Name'];
        yield 'condition' => ['condition', 'new condition'];
        yield 'riskLevel' => ['riskLevel', RiskLevel::HIGH];
        yield 'actions' => ['actions', ['action' => 'block', 'priority' => 100]];
        yield 'priority' => ['priority', 100];
        yield 'terminal' => ['terminal', true];
        yield 'enabled' => ['enabled', false];
        yield 'description' => ['description', 'Test description'];
    }

    public function testConstruct(): void
    {
        $name = 'Test Rule';
        $condition = 'ip_count > 5';
        $riskLevel = RiskLevel::HIGH;
        /** @var array<string, mixed> $actions */
        $actions = ['action1' => 'block', 'action2' => 'log'];
        $priority = 100;
        $terminal = true;

        $rule = new Rule();
        $rule->setName($name);
        $rule->setCondition($condition);
        $rule->setRiskLevel($riskLevel);
        $rule->setActions($actions);
        $rule->setPriority($priority);
        $rule->setTerminal($terminal);

        $this->assertSame($name, $rule->getName());
        $this->assertSame($condition, $rule->getCondition());
        $this->assertSame($riskLevel, $rule->getRiskLevel());
        $this->assertSame($actions, $rule->getActions());
        $this->assertSame($priority, $rule->getPriority());
        $this->assertTrue($rule->isTerminal());
        $this->assertTrue($rule->isEnabled());
        $this->assertNull($rule->getId());
        $this->assertNull($rule->getDescription());
        $this->assertInstanceOf(\DateTimeImmutable::class, $rule->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $rule->getUpdatedAt());
        $this->assertSame(1, $rule->getVersion());
    }

    public function testSettersAndGetters(): void
    {
        $rule = new Rule();
        $rule->setName('Test');
        $rule->setCondition('condition');
        $rule->setRiskLevel(RiskLevel::LOW);

        $rule->setName('New Name');
        $this->assertSame('New Name', $rule->getName());

        $rule->setCondition('new_condition');
        $this->assertSame('new_condition', $rule->getCondition());

        $rule->setRiskLevel(RiskLevel::CRITICAL);
        $this->assertSame(RiskLevel::CRITICAL, $rule->getRiskLevel());

        $newActions = ['action1' => 'throttle', 'action2' => 'notify'];
        $rule->setActions($newActions);
        $this->assertSame($newActions, $rule->getActions());

        $rule->setPriority(200);
        $this->assertSame(200, $rule->getPriority());

        $rule->setTerminal(true);
        $this->assertTrue($rule->isTerminal());

        $rule->setEnabled(false);
        $this->assertFalse($rule->isEnabled());

        $rule->setDescription('Test description');
        $this->assertSame('Test description', $rule->getDescription());
    }

    public function testSetUpdatedTime(): void
    {
        $rule = new Rule();
        $rule->setName('Test');
        $rule->setCondition('condition');
        $rule->setRiskLevel(RiskLevel::MEDIUM);
        $originalUpdatedAt = $rule->getUpdatedAt();

        // Sleep for a microsecond to ensure time difference
        usleep(1);

        $newTime = new \DateTimeImmutable();
        $rule->setUpdatedTime($newTime);

        $this->assertNotEquals($originalUpdatedAt, $rule->getUpdatedAt());
        $this->assertGreaterThan($originalUpdatedAt, $rule->getUpdatedAt());
        $this->assertEquals($newTime, $rule->getUpdatedAt());
    }

    public function testToString(): void
    {
        $rule = new Rule();
        $rule->setName('Test Rule');
        $rule->setCondition('condition');
        $rule->setRiskLevel(RiskLevel::LOW);
        $rule->setActions([]);
        $rule->setPriority(50);
        $rule->setTerminal(false);

        $string = (string) $rule;

        $this->assertStringContainsString('Rule #0', $string);
        $this->assertStringContainsString('[Test Rule]', $string);
        $this->assertStringContainsString('Risk: low', $string);
        $this->assertStringContainsString('Priority: 50', $string);
        $this->assertStringContainsString('Enabled', $string);

        $rule->setEnabled(false);
        $string = (string) $rule;
        $this->assertStringContainsString('Disabled', $string);
    }

    public function testFluentInterface(): void
    {
        $rule = new Rule();
        $rule->setName('Test');
        $rule->setCondition('condition');
        $rule->setRiskLevel(RiskLevel::MEDIUM);

        // Test individual setters (void return type, not fluent)
        $rule->setName('New Name');
        $rule->setCondition('new_condition');
        $rule->setRiskLevel(RiskLevel::HIGH);
        $rule->setActions(['action' => 'block']);
        $rule->setPriority(100);
        $rule->setTerminal(true);
        $rule->setEnabled(false);
        $rule->setDescription('Test');

        // Verify final state
        $this->assertEquals('New Name', $rule->getName());
        $this->assertEquals('new_condition', $rule->getCondition());
        $this->assertEquals(RiskLevel::HIGH, $rule->getRiskLevel());
        $this->assertEquals(['action' => 'block'], $rule->getActions());
        $this->assertEquals(100, $rule->getPriority());
        $this->assertTrue($rule->isTerminal());
        $this->assertFalse($rule->isEnabled());
        $this->assertEquals('Test', $rule->getDescription());
    }
}
