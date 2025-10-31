<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Rule\Engine;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\UserBehavior;
use Tourze\AntiFraudBundle\Rule\Engine\TestRuleEngine;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(TestRuleEngine::class)]
#[RunTestsInSeparateProcesses]
class TestRuleEngineTest extends AbstractIntegrationTestCase
{
    private TestRuleEngine $engine;

    protected function onSetUp(): void
    {
        $this->engine = self::getService(TestRuleEngine::class);
    }

    public function testEvaluateAlwaysReturnsLowRisk(): void
    {
        $userBehavior = new UserBehavior(
            userId: 'test-user',
            sessionId: 'test-session',
            ip: '127.0.0.1',
            userAgent: 'Test/1.0',
            action: 'test'
        );
        $context = new Context($userBehavior);
        $result = $this->engine->evaluate($context);

        $this->assertEquals(RiskLevel::LOW, $result->getRiskLevel());
        $this->assertEmpty($result->getMatchedRules());
        $this->assertEmpty($result->getDetails());
    }

    public function testGetActiveRulesReturnsEmptyArray(): void
    {
        $activeRules = $this->engine->getActiveRules();

        $this->assertIsArray($activeRules);
        $this->assertEmpty($activeRules);
    }

    public function testClearCacheDoesNotThrow(): void
    {
        // 应该不抛出异常
        $this->engine->clearCache();
        $this->assertTrue(true);
    }

    public function testRefreshRulesDoesNotThrow(): void
    {
        // 应该不抛出异常
        $this->engine->refreshRules();
        $this->assertTrue(true);
    }
}
