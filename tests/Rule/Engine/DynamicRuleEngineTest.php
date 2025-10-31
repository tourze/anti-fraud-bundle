<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Rule\Engine;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\UserBehavior;
use Tourze\AntiFraudBundle\Rule\Engine\DynamicRuleEngine;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(DynamicRuleEngine::class)]
#[RunTestsInSeparateProcesses]
final class DynamicRuleEngineTest extends AbstractIntegrationTestCase
{
    private DynamicRuleEngine $engine;

    protected function onSetUp(): void
    {
        $this->engine = self::getService(DynamicRuleEngine::class);
    }

    private function createContext(): Context
    {
        $behavior = new UserBehavior(
            userId: 'user123',
            sessionId: 'session456',
            ip: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
            action: 'login'
        );

        return new Context($behavior);
    }

    public function testEvaluateBasicFunctionality(): void
    {
        $context = $this->createContext();
        $result = $this->engine->evaluate($context);

        $this->assertInstanceOf(RiskLevel::class, $result->getRiskLevel());
        $this->assertIsArray($result->getMatchedRules());
    }

    public function testRefreshRules(): void
    {
        $this->engine->refreshRules();
        $this->expectNotToPerformAssertions();
    }
}
