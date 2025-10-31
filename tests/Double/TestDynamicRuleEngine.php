<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Double;

use Psr\Log\NullLogger;
use Tourze\AntiFraudBundle\Contract\Rule\RuleEngineInterface;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\DetectionResult;
use Tourze\AntiFraudBundle\Rule\Action\LogAction;

/**
 * 测试双对象：模拟动态规则引擎
 *
 * @internal
 */
final class TestDynamicRuleEngine implements RuleEngineInterface
{
    private ?DetectionResult $returnResult = null;

    /** @var array<Context> */
    private array $evaluationHistory = [];

    public function __construct()
    {
        // 默认返回低风险结果
        $this->returnResult = new DetectionResult(
            RiskLevel::LOW,
            new LogAction(new NullLogger(), 'info', 'No risk detected'),
            []
        );
    }

    public function evaluate(Context $context): DetectionResult
    {
        // 记录评估历史用于测试验证
        $this->evaluationHistory[] = $context;

        if (null !== $this->returnResult) {
            return $this->returnResult;
        }

        // 默认行为：返回低风险结果
        return new DetectionResult(
            RiskLevel::LOW,
            new LogAction(new NullLogger(), 'info', 'No risk detected'),
            []
        );
    }

    public function setReturnResult(DetectionResult $result): void
    {
        $this->returnResult = $result;
    }

    /**
     * @return array<Context>
     */
    public function getEvaluationHistory(): array
    {
        return $this->evaluationHistory;
    }

    public function clearEvaluationHistory(): void
    {
        $this->evaluationHistory = [];
    }

    public function getLastContext(): ?Context
    {
        $lastContext = end($this->evaluationHistory);

        return false !== $lastContext ? $lastContext : null;
    }

    public function getEvaluationCount(): int
    {
        return count($this->evaluationHistory);
    }

    public function refreshRules(): void
    {
        // 测试双对象不需要刷新规则，重写为空实现
    }
}
