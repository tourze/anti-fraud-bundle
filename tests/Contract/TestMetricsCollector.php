<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Contract;

use Tourze\AntiFraudBundle\Contract\MetricsCollectorInterface;

/**
 * 测试专用的MetricsCollector接口，添加了用于测试的辅助方法
 */
interface TestMetricsCollector extends MetricsCollectorInterface
{
    /**
     * 设置请求计数（仅用于测试）
     */
    public function setRequestCount(string $identifier, string $period, int $count): void;

    /**
     * 设置用户分数（仅用于测试）
     */
    public function setUserScore(string $userId, float $score): void;

    /**
     * 设置操作计数（仅用于测试）
     */
    public function setActionCount(string $userId, string $action, string $timeWindow, int $count): void;

    /**
     * 设置唯一路径计数（仅用于测试）
     */
    public function setUniquePathsCount(int $count): void;

    /**
     * 设置错误计数（仅用于测试）
     */
    public function setErrorCount(int $count): void;
}
