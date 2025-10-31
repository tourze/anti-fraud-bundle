<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Service;

use Tourze\AntiFraudBundle\Entity\RiskProfile;
use Tourze\AntiFraudBundle\Enum\RiskLevel;

/**
 * 风险等级计算服务
 * 负责根据风险档案数据计算风险等级
 */
class RiskLevelCalculator
{
    public function calculateRiskLevel(RiskProfile $profile): RiskLevel
    {
        if ($profile->isBlacklisted()) {
            return RiskLevel::CRITICAL;
        }

        if ($profile->isWhitelisted()) {
            return RiskLevel::LOW;
        }

        // Calculate risk level based on detection history
        $totalDetections = max($profile->getTotalDetections(), 1);
        $highRiskRatio = $profile->getHighRiskDetections() / $totalDetections;

        if ($highRiskRatio > 0.5 || $profile->getBlockedActions() > 5) {
            return RiskLevel::HIGH;
        }

        if ($highRiskRatio > 0.3 || $profile->getBlockedActions() > 2) {
            return RiskLevel::MEDIUM;
        }

        return RiskLevel::LOW;
    }
}
