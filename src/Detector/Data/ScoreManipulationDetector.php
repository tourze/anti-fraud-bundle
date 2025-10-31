<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Detector\Data;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\AntiFraudBundle\Detector\AbstractDetector;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\DetectionResult;
use Tourze\AntiFraudBundle\Model\RiskAssessment;
use Tourze\AntiFraudBundle\Service\MetricsCollector;

/**
 * 分数/数据篡改检测器
 * 检测用户是否尝试篡改分数、金额等敏感数据
 */
#[WithMonologChannel(channel: 'anti_fraud')]
class ScoreManipulationDetector extends AbstractDetector
{
    // 数值异常阈值
    private const MAX_SCORE_INCREASE_RATE = 2.0; // 单次最大增长倍数
    private const MAX_DAILY_SCORE_INCREASE = 10000; // 每日最大增长值
    private const SUSPICIOUS_PATTERNS = [
        '/999+$/', // 连续的9
        '/000+$/', // 连续的0
        '/(\d)\1{4,}/', // 重复数字
    ];

    public function __construct(
        private MetricsCollector $metricsCollector,
        private LoggerInterface $logger,
    ) {
        parent::__construct('score_manipulation_detector');
    }

    protected function performDetection(Context $context): DetectionResult
    {
        $userId = $context->getUserId();
        $action = $context->getAttribute('action');

        // 只检查涉及分数/金额变动的操作
        if (!is_string($action) || !$this->isScoreRelatedAction($action)) {
            return $this->createSkippedResult();
        }

        $manipulationIndicators = $this->collectManipulationIndicators($context, $userId, $action);
        $riskScore = $this->calculateRiskScore($manipulationIndicators);
        $riskLevel = $this->calculateRiskLevel($riskScore);

        $details = [
            'manipulation_indicators' => $manipulationIndicators,
            'risk_score' => $riskScore,
            'user_id' => $userId,
            'action' => $action,
        ];

        $this->logSuspiciousActivity($riskLevel, $details);

        $assessment = new RiskAssessment(
            $riskLevel->getScore(),
            $riskLevel,
            count($manipulationIndicators) > 0 ? ['detection' => 'Score manipulation detected'] : ['detection' => 'No manipulation detected']
        );

        return new DetectionResult($assessment, $details);
    }

    private function createSkippedResult(): DetectionResult
    {
        $assessment = new RiskAssessment(
            RiskLevel::LOW->getScore(),
            RiskLevel::LOW,
            ['reason' => 'Not score related action']
        );

        return new DetectionResult($assessment, ['skipped' => true, 'reason' => 'not_score_related']);
    }

    /**
     * @return array<string, mixed>
     */
    private function collectManipulationIndicators(Context $context, string $userId, string $action): array
    {
        $manipulationIndicators = [];

        // 检查数值模式
        $scoreData = $context->getAttribute('score_data', []);
        // 确保 scoreData 是数组类型
        if (!is_array($scoreData)) {
            $scoreData = [];
        }

        /** @var array<string, mixed> $scoreData */
        if (count($scoreData) > 0) {
            $patternCheck = $this->checkSuspiciousPatterns($scoreData);
            if ($patternCheck['suspicious']) {
                $manipulationIndicators['suspicious_patterns'] = $patternCheck;
            }
        }

        // 检查增长率
        $growthCheck = $this->checkAbnormalGrowth($userId, $scoreData);
        if ($growthCheck['is_abnormal']) {
            $manipulationIndicators['abnormal_growth'] = $growthCheck;
        }

        // 检查请求篡改
        $tamperCheck = $this->checkRequestTampering($context);
        if ($tamperCheck['detected']) {
            $manipulationIndicators['tampering'] = $tamperCheck;
        }

        // 检查异常提交频率
        $frequencyCheck = $this->checkSubmissionFrequency($userId, $action);
        if ($frequencyCheck['is_abnormal']) {
            $manipulationIndicators['abnormal_frequency'] = $frequencyCheck;
        }

        return $manipulationIndicators;
    }

    /**
     * @param array<string, mixed> $manipulationIndicators
     */
    private function calculateRiskScore(array $manipulationIndicators): float
    {
        $riskScore = 0.0;

        if (isset($manipulationIndicators['suspicious_patterns'])) {
            $riskScore += 0.4;
        }

        if (isset($manipulationIndicators['abnormal_growth'])) {
            $riskScore += 0.5;
        }

        if (isset($manipulationIndicators['tampering'])) {
            $riskScore += 0.6;
        }

        if (isset($manipulationIndicators['abnormal_frequency'])) {
            $riskScore += 0.3;
        }

        return $riskScore;
    }

    /**
     * @param array<string, mixed> $details
     */
    private function logSuspiciousActivity(RiskLevel $riskLevel, array $details): void
    {
        if (RiskLevel::LOW !== $riskLevel) {
            $this->logger->warning('Score manipulation detected', [
                'detector' => $this->getName(),
                'details' => $details,
            ]);
        }
    }

    private function isScoreRelatedAction(string $action): bool
    {
        $scoreActions = [
            'update_score',
            'add_points',
            'transfer_points',
            'claim_reward',
            'purchase',
            'withdrawal',
            'deposit',
        ];

        foreach ($scoreActions as $scoreAction) {
            if (false !== stripos($action, $scoreAction)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed> $scoreData
     * @return array{suspicious: bool, patterns: array<int, array<string, mixed>>}
     */
    private function checkSuspiciousPatterns(array $scoreData): array
    {
        $suspicious = false;
        $matchedPatterns = [];

        foreach ($scoreData as $key => $value) {
            if (!is_numeric($value)) {
                continue;
            }

            $valueStr = (string) $value;

            foreach (self::SUSPICIOUS_PATTERNS as $pattern) {
                if (1 === preg_match($pattern, $valueStr)) {
                    $suspicious = true;
                    $matchedPatterns[] = [
                        'field' => $key,
                        'value' => $value,
                        'pattern' => $pattern,
                    ];
                }
            }

            // 检查异常大的数值
            if ((int) $value > 1000000) {
                $suspicious = true;
                $matchedPatterns[] = [
                    'field' => $key,
                    'value' => $value,
                    'reason' => 'unusually_large_value',
                ];
            }
        }

        return [
            'suspicious' => $suspicious,
            'patterns' => $matchedPatterns,
        ];
    }

    /**
     * @param array<string, mixed> $scoreData
     * @return array{is_abnormal: bool, growth_rate?: float, details?: array<string, mixed>}
     */
    private function checkAbnormalGrowth(string $userId, array $scoreData): array
    {
        $currentScore = $scoreData['current_score'] ?? 0;
        // 确保当前分数是数值类型
        if (!is_numeric($currentScore)) {
            $currentScore = 0;
        }
        $currentScore = (float) $currentScore;

        $previousScore = $scoreData['previous_score'] ?? null;

        if (null === $previousScore) {
            $previousScore = $this->metricsCollector->getUserLastScore($userId);
        }

        // 确保前一分数是数值类型
        if (null === $previousScore || !is_numeric($previousScore) || 0.0 === (float) $previousScore) {
            return ['is_abnormal' => false, 'reason' => 'no_previous_data'];
        }
        $previousScore = (float) $previousScore;

        $increase = $currentScore - $previousScore;
        $growthRate = $currentScore / $previousScore;

        // 检查单次增长率
        if ($growthRate > self::MAX_SCORE_INCREASE_RATE) {
            return [
                'is_abnormal' => true,
                'growth_rate' => $growthRate,
                'threshold' => self::MAX_SCORE_INCREASE_RATE,
                'previous' => $previousScore,
                'current' => $currentScore,
                'type' => 'excessive_growth_rate',
            ];
        }

        // 检查每日总增长
        $dailyIncrease = $this->metricsCollector->getUserDailyScoreIncrease($userId);
        if ($dailyIncrease + $increase > self::MAX_DAILY_SCORE_INCREASE) {
            return [
                'is_abnormal' => true,
                'daily_increase' => $dailyIncrease + $increase,
                'threshold' => self::MAX_DAILY_SCORE_INCREASE,
                'type' => 'excessive_daily_increase',
            ];
        }

        return ['is_abnormal' => false];
    }

    /**
     * @return array{detected: bool, indicators?: array<string, mixed>}
     */
    private function checkRequestTampering(Context $context): array
    {
        $indicators = [];
        $detected = false;

        // 检查签名
        if ($this->hasInvalidSignature($context)) {
            $detected = true;
            $indicators['invalid_signature'] = true;
        }

        // 检查参数冲突
        $parameterConflicts = $this->detectParameterConflicts($context);
        if ([] !== $parameterConflicts) {
            $detected = true;
            $indicators['parameter_conflicts'] = $parameterConflicts;
        }

        return [
            'detected' => $detected,
            'indicators' => $indicators,
        ];
    }

    /**
     * 检查签名是否无效
     */
    private function hasInvalidSignature(Context $context): bool
    {
        $signature = $context->getAttribute('request_signature');

        return null !== $signature && is_string($signature) && !$this->verifySignature($context, $signature);
    }

    /**
     * 检测参数冲突
     * @return array<int, string>
     */
    private function detectParameterConflicts(Context $context): array
    {
        $formData = $this->normalizeArrayData($context->getAttribute('form_data', []));
        $queryData = $this->normalizeArrayData($context->getAttribute('query_data', []));

        $conflicts = array_intersect_key($formData, $queryData);
        $conflictKeys = [];

        foreach ($conflicts as $key => $value) {
            if ($formData[$key] !== $queryData[$key]) {
                $conflictKeys[] = $key;
            }
        }

        return $conflictKeys;
    }

    /**
     * 标准化数组数据
     * @param mixed $data
     * @return array<string, mixed>
     */
    private function normalizeArrayData($data): array
    {
        if (!is_array($data)) {
            return [];
        }

        $result = [];
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function verifySignature(Context $context, string $signature): bool
    {
        // 简化的签名验证逻辑
        // 实际应用中应该使用更复杂的签名算法
        $data = $context->getAttribute('signed_data', '');
        if (!is_string($data)) {
            $data = '';
        }

        $secret = $_ENV['ANTIFRAUD_SECRET'] ?? 'default-secret';
        if (!is_string($secret)) {
            $secret = 'default-secret';
        }

        $expectedSignature = hash_hmac('sha256', $data, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * @return array{is_abnormal: bool, frequency?: float, threshold?: float}
     */
    private function checkSubmissionFrequency(string $userId, string $action): array
    {
        $recentSubmissions = $this->metricsCollector->getActionCount($userId, $action, '5m');

        // 5分钟内同一操作超过10次视为异常
        if ($recentSubmissions > 10) {
            return [
                'is_abnormal' => true,
                'count' => $recentSubmissions,
                'time_window' => '5m',
                'threshold' => 10,
            ];
        }

        return ['is_abnormal' => false];
    }

    private function calculateRiskLevel(float $riskScore): RiskLevel
    {
        if ($riskScore >= 0.8) {
            return RiskLevel::CRITICAL;
        }
        if ($riskScore >= 0.6) {
            return RiskLevel::HIGH;
        }
        if ($riskScore >= 0.3) {
            return RiskLevel::MEDIUM;
        }

        return RiskLevel::LOW;
    }
}
