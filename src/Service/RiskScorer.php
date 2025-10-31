<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\AntiFraudBundle\Contract\DetectorInterface;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\DetectionResult;
use Tourze\AntiFraudBundle\Model\RiskAssessment;
use Tourze\AntiFraudBundle\Repository\RiskProfileRepository;

/**
 * 风险评分服务
 * 综合多个检测器的结果，计算最终的风险评分
 */
#[WithMonologChannel(channel: 'anti_fraud')]
class RiskScorer
{
    /** @var DetectorInterface[] */
    private array $detectors = [];

    // 权重配置
    /** @var array<string, float> */
    private array $detectorWeights = [
        'multi_account_detector' => 0.25,
        'proxy_detector' => 0.20,
        'abnormal_pattern_detector' => 0.20,
        'score_manipulation_detector' => 0.15,
        'automation_detector' => 0.20,
    ];

    public function __construct(
        private readonly RiskProfileRepository $profileRepository,
        private readonly RiskProfileService $profileService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function addDetector(DetectorInterface $detector): void
    {
        $this->detectors[$detector->getName()] = $detector;
    }

    public function assessRisk(Context $context): RiskAssessment
    {
        /** @var array<string, DetectionResult> $detectionResults */
        $detectionResults = [];
        $weightedScore = 0.0;
        $maxRiskLevel = RiskLevel::LOW;
        $totalWeight = 0.0;

        // 运行所有启用的检测器
        foreach ($this->detectors as $detector) {
            if (!$detector->isEnabled()) {
                continue;
            }

            try {
                $result = $detector->detect($context);
                $detectionResults[$detector->getName()] = $result;

                // 计算加权分数
                $weight = $this->detectorWeights[$detector->getName()] ?? 0.1;
                $score = $this->riskLevelToScore($result->getRiskLevel());
                $weightedScore += $score * $weight;
                $totalWeight += $weight;

                // 跟踪最高风险等级
                if ($this->compareRiskLevels($result->getRiskLevel(), $maxRiskLevel) > 0) {
                    $maxRiskLevel = $result->getRiskLevel();
                }
            } catch (\Exception $e) {
                $this->logger->error('Detector failed', [
                    'detector' => $detector->getName(),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // 计算最终分数
        $finalScore = $totalWeight > 0 ? $weightedScore / $totalWeight : 0.0;

        // 考虑历史风险档案
        $profileAdjustment = $this->getProfileRiskAdjustment($context);
        $finalScore = min(1.0, $finalScore + $profileAdjustment);

        // 确定最终风险等级
        $finalRiskLevel = $this->scoreToRiskLevel($finalScore);

        // 如果有任何检测器返回CRITICAL，最终结果至少是HIGH
        if (RiskLevel::CRITICAL === $maxRiskLevel && RiskLevel::CRITICAL !== $finalRiskLevel) {
            $finalRiskLevel = RiskLevel::HIGH;
        }

        // 更新风险档案
        $this->updateRiskProfile($context, $finalRiskLevel, $detectionResults);

        return new RiskAssessment(
            $finalScore,
            $finalRiskLevel,
            $detectionResults,
            [
                'weighted_score' => $weightedScore,
                'total_weight' => $totalWeight,
                'profile_adjustment' => $profileAdjustment,
                'max_risk_level' => $maxRiskLevel->value,
            ]
        );
    }

    private function riskLevelToScore(RiskLevel $level): float
    {
        return match ($level) {
            RiskLevel::LOW => 0.1,
            RiskLevel::MEDIUM => 0.4,
            RiskLevel::HIGH => 0.7,
            RiskLevel::CRITICAL => 1.0,
        };
    }

    private function scoreToRiskLevel(float $score): RiskLevel
    {
        if ($score >= 0.8) {
            return RiskLevel::CRITICAL;
        }
        if ($score >= 0.6) {
            return RiskLevel::HIGH;
        }
        if ($score >= 0.3) {
            return RiskLevel::MEDIUM;
        }

        return RiskLevel::LOW;
    }

    private function compareRiskLevels(RiskLevel $a, RiskLevel $b): int
    {
        /** @var array<string, int> $levels */
        $levels = [
            RiskLevel::LOW->value => 1,
            RiskLevel::MEDIUM->value => 2,
            RiskLevel::HIGH->value => 3,
            RiskLevel::CRITICAL->value => 4,
        ];

        return $levels[$a->value] <=> $levels[$b->value];
    }

    private function getProfileRiskAdjustment(Context $context): float
    {
        $adjustment = 0.0;

        // 检查用户风险档案
        $userProfile = $this->profileRepository->findByIdentifier(
            'user',
            $context->getUserId()
        );

        if (null !== $userProfile) {
            if ($userProfile->isBlacklisted()) {
                return 0.5; // 黑名单用户增加50%风险分
            }
            if ($userProfile->isWhitelisted()) {
                return -0.3; // 白名单用户减少30%风险分
            }

            // 基于历史行为调整
            $riskScore = $userProfile->getRiskScore();
            $adjustment += $riskScore * 0.2; // 历史风险分的20%
        }

        // 检查IP风险档案
        $ipProfile = $this->profileRepository->findByIdentifier(
            'ip',
            $context->getIp()
        );

        if (null !== $ipProfile) {
            if ($ipProfile->isBlacklisted()) {
                $adjustment += 0.3;
            } elseif ($ipProfile->getRiskScore() > 0.5) {
                $adjustment += $ipProfile->getRiskScore() * 0.1;
            }
        }

        return $adjustment;
    }

    /**
     * @param array<string, DetectionResult> $detectionResults
     */
    private function updateRiskProfile(Context $context, RiskLevel $riskLevel, array $detectionResults): void
    {
        $actionTaken = $this->determineActionTaken($detectionResults);

        // 更新用户风险档案
        $this->profileService->updateProfileStats(
            'user',
            $context->getUserId(),
            $riskLevel,
            $actionTaken
        );

        // 更新IP风险档案
        $this->profileService->updateProfileStats(
            'ip',
            $context->getIp(),
            $riskLevel,
            $actionTaken
        );
    }

    /**
     * @param array<string, DetectionResult> $detectionResults
     */
    private function determineActionTaken(array $detectionResults): ?string
    {
        foreach ($detectionResults as $result) {
            if ($result instanceof DetectionResult) {
                $action = $result->getAction();
                $actionName = $action->getName();

                if ('block' === $actionName) {
                    return 'block';
                }
                if ('throttle' === $actionName) {
                    return 'throttle';
                }
            }
        }

        return null;
    }
}
