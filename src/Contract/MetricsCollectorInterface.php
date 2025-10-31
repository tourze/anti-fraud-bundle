<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Contract;

interface MetricsCollectorInterface
{
    /**
     * 获取指定IP在给定时间窗口内的请求数量
     *
     * @param string $ip IP地址
     * @param string $timeWindow 时间窗口 (例如: '1m', '5m', '1h')
     */
    public function getRequestCount(string $ip, string $timeWindow): int;

    /**
     * 获取给定标识符的唯一用户数量
     *
     * @param string $identifier 要检查的标识符 (例如: IP地址)
     * @param string $type 标识符类型 (例如: 'ip', 'user_id')
     * @param int    $seconds   向前查询的秒数
     */
    public function getUniqueUsersCount(string $identifier, string $type, int $seconds): int;

    /**
     * 获取用户在时间窗口内访问的唯一路径数量
     *
     * @param string $userId     用户ID
     * @param string $timeWindow 时间窗口 (例如: '1h', '24h')
     */
    public function getUniquePathsCount(string $userId, string $timeWindow): int;

    /**
     * 获取用户在时间窗口内的错误数量
     *
     * @param string $userId     用户ID
     * @param string $timeWindow 时间窗口 (例如: '1h', '24h')
     */
    public function getErrorCount(string $userId, string $timeWindow): int;

    /**
     * 获取用户的登录次数
     *
     * @param string $userId 用户ID
     */
    public function getLoginCount(string $userId): int;

    /**
     * 获取用户最后已知的风险分数
     *
     * @param string $userId 用户ID
     *
     * @return float|null 风险分数，如果未找到则返回null
     */
    public function getUserLastScore(string $userId): ?float;

    /**
     * 获取用户每日分数增长
     *
     * @param string $userId 用户ID
     *
     * @return float 每日分数增长
     */
    public function getUserDailyScoreIncrease(string $userId): float;

    /**
     * 获取用户在时间窗口内的操作数量
     *
     * @param string $userId     用户ID
     * @param string $action     操作名称
     * @param string $timeWindow 时间窗口 (例如: '1h', '24h')
     */
    public function getActionCount(string $userId, string $action, string $timeWindow): int;

    /**
     * 获取会话的每秒操作数
     *
     * @param string $sessionId 会话ID
     *
     * @return float 每秒操作数
     */
    public function getActionsPerSecond(string $sessionId): float;

    /**
     * 获取会话的操作时间模式
     *
     * @param string $sessionId 会话ID
     * @param int    $limit      返回的时间戳最大数量
     *
     * @return array<float> 操作时间戳数组
     */
    public function getActionTimings(string $sessionId, int $limit): array;

    /**
     * 增加请求计数
     *
     * @param string $ip IP地址
     */
    public function incrementRequestCount(string $ip): void;

    /**
     * 增加登录计数
     *
     * @param string $userId 用户ID
     */
    public function incrementLoginCount(string $userId): void;
}
