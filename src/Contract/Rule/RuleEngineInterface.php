<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Contract\Rule;

use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\DetectionResult;

/**
 * 规则引擎接口
 */
interface RuleEngineInterface
{
    /**
     * 评估给定上下文并返回检测结果
     */
    public function evaluate(Context $context): DetectionResult;

    /**
     * 刷新规则缓存
     */
    public function refreshRules(): void;
}
