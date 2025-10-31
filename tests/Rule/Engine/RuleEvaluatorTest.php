<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Rule\Engine;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tourze\AntiFraudBundle\Entity\Rule;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\UserBehavior;
use Tourze\AntiFraudBundle\Rule\Engine\RuleEvaluator;
use Tourze\AntiFraudBundle\Service\MetricsCollector;
use Tourze\AntiFraudBundle\Tests\Double\TestMetricsCollectorImpl;

/**
 * @internal
 */
#[CoversClass(RuleEvaluator::class)]
final class RuleEvaluatorTest extends TestCase
{
    private TestMetricsCollectorImpl $metricsCollector;

    private RuleEvaluator $evaluator;

    protected function setUp(): void
    {
        $this->metricsCollector = new TestMetricsCollectorImpl();
        $this->evaluator = new RuleEvaluator($this->metricsCollector);
    }

    private function createContext(string $path = '/login', string $ip = '192.168.1.1'): Context
    {
        $behavior = new UserBehavior(
            userId: 'user123',
            sessionId: 'session456',
            ip: $ip,
            userAgent: 'Mozilla/5.0',
            action: 'login'
        );

        $context = new Context($behavior);
        $context->setAttribute('path', $path);

        return $context;
    }

    private function createRule(string $condition): Rule
    {
        $rule = new Rule();
        $rule->setName('test_rule');
        $rule->setCondition($condition);
        $rule->setRiskLevel(RiskLevel::HIGH);
        $rule->setActions(['action' => 'block']);

        return $rule;
    }

    public function testSimplePathMatch(): void
    {
        $rule = $this->createRule('request["path"] == "/login"');
        $context = $this->createContext('/login');

        $this->assertTrue($this->evaluator->matches($rule, $context));
    }

    public function testSimplePathNoMatch(): void
    {
        $rule = $this->createRule('request["path"] == "/login"');
        $context = $this->createContext('/register');

        $this->assertFalse($this->evaluator->matches($rule, $context));
    }

    public function testIpMatch(): void
    {
        $rule = $this->createRule('request["ip"] == "192.168.1.1"');
        $context = $this->createContext('/login', '192.168.1.1');

        $this->assertTrue($this->evaluator->matches($rule, $context));
    }

    public function testComplexCondition(): void
    {
        $rule = $this->createRule('request["path"] == "/login" and request["ip"] == "192.168.1.1"');
        $context = $this->createContext('/login', '192.168.1.1');

        $this->assertTrue($this->evaluator->matches($rule, $context));
    }

    public function testPathContains(): void
    {
        $rule = $this->createRule('request["path"] starts with "/api"');
        $context = $this->createContext('/api/users');

        $this->assertTrue($this->evaluator->matches($rule, $context));
    }

    public function testInOperator(): void
    {
        $rule = $this->createRule('request["path"] in ["/login", "/register", "/reset"]');
        $context = $this->createContext('/register');

        $this->assertTrue($this->evaluator->matches($rule, $context));
    }

    public function testUserAgentCondition(): void
    {
        $rule = $this->createRule('request["user_agent"] matches "/(bot|crawler|spider)/i"');
        $behavior = new UserBehavior(
            userId: 'user123',
            sessionId: 'session456',
            ip: '192.168.1.1',
            userAgent: 'Googlebot/2.1',
            action: 'crawl'
        );
        $context = new Context($behavior);

        $this->assertTrue($this->evaluator->matches($rule, $context));
    }

    public function testProxyIpCondition(): void
    {
        $rule = $this->createRule('ip["is_proxy"] == true');
        $context = $this->createContext();
        $context->setAttribute('is_proxy', true);

        $this->assertTrue($this->evaluator->matches($rule, $context));
    }

    public function testFormSubmitTimeCondition(): void
    {
        $rule = $this->createRule('form["submit_time"] < 2');
        $context = $this->createContext();
        $context->setAttribute('form_submit_time', 1.5);

        $this->assertTrue($this->evaluator->matches($rule, $context));
    }

    public function testRequestCountFunction(): void
    {
        $this->metricsCollector->setRequestCount('192.168.1.1', '5m', 10);

        $rule = $this->createRule('request_count("5m") > 5');
        $context = $this->createContext();

        $this->assertTrue($this->evaluator->matches($rule, $context));
    }

    public function testNewUserCondition(): void
    {
        $rule = $this->createRule('user["is_new"] == true');
        $context = $this->createContext();
        $context->setAttribute('is_new_user', true);

        $this->assertTrue($this->evaluator->matches($rule, $context));
    }

    public function testInvalidRuleReturnsFalse(): void
    {
        $rule = $this->createRule('invalid.syntax.here');
        $context = $this->createContext();

        $this->assertFalse($this->evaluator->matches($rule, $context));
    }

    public function testDisabledRuleReturnsFalse(): void
    {
        $rule = $this->createRule('request["path"] == "/login"');
        $rule->setEnabled(false);
        $context = $this->createContext('/login');

        $this->assertFalse($this->evaluator->matches($rule, $context));
    }

    public function testMatches(): void
    {
        $rule = $this->createRule('request["path"] == "/api/test"');
        $context = $this->createContext('/api/test');

        $this->assertTrue($this->evaluator->matches($rule, $context));

        $rule2 = $this->createRule('request["path"] == "/api/other"');
        $this->assertFalse($this->evaluator->matches($rule2, $context));
    }
}
