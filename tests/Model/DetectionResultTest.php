<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\DetectionResult;
use Tourze\AntiFraudBundle\Tests\Double\TestAction;
use Tourze\AntiFraudBundle\Tests\Double\TestRule;

/**
 * @internal
 */
#[CoversClass(DetectionResult::class)]
final class DetectionResultTest extends TestCase
{
    protected function setUp(): void
    {
        // 无需特殊设置，使用父类的默认行为
    }

    public function testConstructorAndGetters(): void
    {
        $action = new TestAction('block');

        $rule1 = new TestRule('rate_limit_login');
        $rule2 = new TestRule('suspicious_ip');
        $matchedRules = [$rule1->getRule(), $rule2->getRule()];

        $result = new DetectionResult(
            RiskLevel::HIGH,
            $action,
            $matchedRules
        );

        $this->assertSame(RiskLevel::HIGH, $result->getRiskLevel());
        $this->assertSame($action, $result->getAction());
        $this->assertSame($matchedRules, $result->getMatchedRules());
        $this->assertCount(2, $result->getMatchedRules());
    }

    public function testShouldTakeAction(): void
    {
        $action = new TestAction('log');

        $lowRiskResult = new DetectionResult(RiskLevel::LOW, $action, []);
        $mediumRiskResult = new DetectionResult(RiskLevel::MEDIUM, $action, []);
        $highRiskResult = new DetectionResult(RiskLevel::HIGH, $action, []);
        $criticalRiskResult = new DetectionResult(RiskLevel::CRITICAL, $action, []);

        $this->assertFalse($lowRiskResult->shouldTakeAction());
        $this->assertFalse($mediumRiskResult->shouldTakeAction());
        $this->assertTrue($highRiskResult->shouldTakeAction());
        $this->assertTrue($criticalRiskResult->shouldTakeAction());
    }

    public function testGetMatchedRuleNames(): void
    {
        $action = new TestAction('block');

        $rule1 = new TestRule('rate_limit_login');
        $rule2 = new TestRule('suspicious_ip');

        $result = new DetectionResult(
            RiskLevel::HIGH,
            $action,
            [$rule1->getRule(), $rule2->getRule()]
        );

        $ruleNames = $result->getMatchedRuleNames();
        $this->assertCount(2, $ruleNames);
        $this->assertContains('rate_limit_login', $ruleNames);
        $this->assertContains('suspicious_ip', $ruleNames);
    }

    public function testHasMatchedRules(): void
    {
        $action = new TestAction('block');
        $rule = new TestRule('test_rule');

        $resultWithRules = new DetectionResult(RiskLevel::HIGH, $action, [$rule->getRule()]);
        $resultWithoutRules = new DetectionResult(RiskLevel::LOW, $action, []);

        $this->assertTrue($resultWithRules->hasMatchedRules());
        $this->assertFalse($resultWithoutRules->hasMatchedRules());
    }

    public function testToArray(): void
    {
        $action = new TestAction('block');

        $rule1 = new TestRule('rate_limit_login');
        $rule2 = new TestRule('suspicious_ip');

        $result = new DetectionResult(
            RiskLevel::HIGH,
            $action,
            [$rule1->getRule(), $rule2->getRule()]
        );

        $array = $result->toArray();

        $this->assertArrayHasKey('riskLevel', $array);
        $this->assertArrayHasKey('action', $array);
        $this->assertArrayHasKey('matchedRules', $array);
        $this->assertArrayHasKey('shouldTakeAction', $array);

        $this->assertSame('high', $array['riskLevel']);
        $this->assertSame('block', $array['action']);
        $this->assertSame(['rate_limit_login', 'suspicious_ip'], $array['matchedRules']);
        $this->assertTrue($array['shouldTakeAction']);
    }
}
