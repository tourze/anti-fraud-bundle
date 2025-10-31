<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Service;

use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Repository\RiskProfileRepository;

/**
 * 风险档案服务
 * 负责更新和管理风险档案统计数据
 */
class RiskProfileService
{
    public function __construct(
        private readonly RiskProfileRepository $repository,
        private readonly RiskLevelCalculator $calculator,
    ) {
    }

    /**
     * 更新风险概况统计数据
     */
    public function updateProfileStats(string $type, string $value, RiskLevel $detectionLevel, ?string $actionTaken = null): void
    {
        $profile = $this->repository->findOrCreate($type, $value);

        // Increment counters
        $profile->incrementTotalDetections();
        $profile->setLastDetectionAt(new \DateTimeImmutable());

        switch ($detectionLevel) {
            case RiskLevel::HIGH:
            case RiskLevel::CRITICAL:
                $profile->incrementHighRiskDetections();
                $profile->setLastHighRiskAt(new \DateTimeImmutable());
                break;
            case RiskLevel::MEDIUM:
                $profile->incrementMediumRiskDetections();
                break;
            case RiskLevel::LOW:
                $profile->incrementLowRiskDetections();
                break;
        }

        if ('block' === $actionTaken) {
            $profile->incrementBlockedActions();
        } elseif ('throttle' === $actionTaken) {
            $profile->incrementThrottledActions();
        }

        // Update risk level using calculator service
        $newRiskLevel = $this->calculator->calculateRiskLevel($profile);
        $profile->setRiskLevel($newRiskLevel);

        $this->repository->save($profile, true);
    }
}
