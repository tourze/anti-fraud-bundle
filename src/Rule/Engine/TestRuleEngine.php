<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Rule\Engine;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\AntiFraudBundle\Contract\Rule\RuleEngineInterface;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\DetectionResult;
use Tourze\AntiFraudBundle\Rule\Action\LogAction;
use Tourze\AntiFraudBundle\Rule\Rule;

/**
 * 测试专用的规则引擎，不会阻塞测试执行
 */
#[WithMonologChannel(channel: 'anti_fraud')]
#[Autoconfigure(public: true)]
class TestRuleEngine implements RuleEngineInterface
{
    public function __construct(
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    public function evaluate(Context $context): DetectionResult
    {
        // 在测试环境中，始终返回低风险结果，不阻塞任何操作
        $allowAction = new LogAction(
            $this->logger,
            'info',
            'Test environment - allowing all requests'
        );

        return new DetectionResult(
            RiskLevel::LOW,
            $allowAction,
            [],
            []
        );
    }

    /**
     * @return array<int, Rule>
     */
    public function getActiveRules(): array
    {
        // 在测试环境中不使用任何活动规则
        return [];
    }

    public function clearCache(): void
    {
        // 测试环境中无需清除缓存
    }

    public function refreshRules(): void
    {
        // 测试环境中无需刷新规则
    }
}
