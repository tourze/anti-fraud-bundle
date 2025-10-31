<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Double;

use Tourze\AntiFraudBundle\Service\MetricsCollector;

/**
 * 简单的测试用 MetricsCollector 实现
 * @internal
 */
final class TestMetricsCollectorImpl implements MetricsCollector
{
    /**
     * @var array<string, int>
     */
    private array $requestCounts = [];

    /**
     * @var array<string, float>
     */
    private array $userScores = [];

    /**
     * @var array<string, int>
     */
    private array $actionCounts = [];

    public function getRequestCount(string $ip, string $timeWindow): int
    {
        return $this->requestCounts[$ip . '_' . $timeWindow] ?? 0;
    }

    public function setRequestCount(string $identifier, string $period, int $count): void
    {
        $this->requestCounts[$identifier . '_' . $period] = $count;
    }

    public function getUniqueUsersCount(string $identifier, string $type, int $seconds): int
    {
        return 0;
    }

    public function getLoginCount(string $userId): int
    {
        return 0;
    }

    public function incrementRequestCount(string $ip): void
    {
        // 空实现，用于测试
    }

    public function incrementLoginCount(string $userId): void
    {
        // 空实现，用于测试
    }

    public function getActionsPerSecond(string $sessionId): float
    {
        return 0.0;
    }

    /**
     * @return array<float>
     */
    public function getActionTimings(string $sessionId, int $limit): array
    {
        return [];
    }

    public function getUserLastScore(string $userId): ?float
    {
        return $this->userScores[$userId] ?? null;
    }

    public function setUserScore(string $userId, float $score): void
    {
        $this->userScores[$userId] = $score;
    }

    public function getUserDailyScoreIncrease(string $userId): float
    {
        return 0.0;
    }

    public function getActionCount(string $userId, string $action, string $timeWindow): int
    {
        $key = $userId . '_' . $action . '_' . $timeWindow;

        return $this->actionCounts[$key] ?? 0;
    }

    public function setActionCount(string $userId, string $action, string $timeWindow, int $count): void
    {
        $key = $userId . '_' . $action . '_' . $timeWindow;
        $this->actionCounts[$key] = $count;
    }

    public function getUniquePathsCount(string $userId, string $timeWindow): int
    {
        return 0;
    }

    public function getErrorCount(string $userId, string $timeWindow): int
    {
        return 0;
    }
}
