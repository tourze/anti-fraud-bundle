<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Detector\Data;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\AntiFraudBundle\Contract\MetricsCollectorInterface;
use Tourze\AntiFraudBundle\Detector\AbstractDetector;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\DetectionResult;
use Tourze\AntiFraudBundle\Model\RiskAssessment;

/**
 * 异常模式检测器
 * 检测用户行为中的异常模式，如访问频率异常、请求模式异常等
 */
#[WithMonologChannel(channel: 'anti_fraud')]
class AbnormalPatternDetector extends AbstractDetector
{
    // 正常行为阈值
    private const NORMAL_REQUEST_RATE_PER_MINUTE = 30;
    private const NORMAL_UNIQUE_PATHS_PER_HOUR = 50;
    private const NORMAL_ERROR_RATE = 0.1; // 10%
    private const MIN_REQUESTS_FOR_ANALYSIS = 10;

    public function __construct(
        private MetricsCollectorInterface $metricsCollector,
        private LoggerInterface $logger,
    ) {
        parent::__construct('abnormal_pattern_detector');
    }

    protected function performDetection(Context $context): DetectionResult
    {
        $userId = $context->getUserId();
        $ip = $context->getIp();

        $anomalies = [];
        $riskScore = 0.0;

        // 检查请求频率
        $requestRateAnomaly = $this->checkRequestRate($ip);
        if ($requestRateAnomaly['is_anomaly']) {
            $anomalies['request_rate'] = $requestRateAnomaly;
            $riskScore += $requestRateAnomaly['severity'] * 0.3;
        }

        // 检查访问模式多样性
        $pathDiversityAnomaly = $this->checkPathDiversity($userId);
        if ($pathDiversityAnomaly['is_anomaly']) {
            $anomalies['path_diversity'] = $pathDiversityAnomaly;
            $riskScore += $pathDiversityAnomaly['severity'] * 0.2;
        }

        // 检查错误率
        $errorRateAnomaly = $this->checkErrorRate($userId);
        if ($errorRateAnomaly['is_anomaly']) {
            $anomalies['error_rate'] = $errorRateAnomaly;
            $riskScore += $errorRateAnomaly['severity'] * 0.3;
        }

        // 检查时间模式
        $timePatternAnomaly = $this->checkTimePattern($userId);
        if ($timePatternAnomaly['is_anomaly']) {
            $anomalies['time_pattern'] = $timePatternAnomaly;
            $riskScore += $timePatternAnomaly['severity'] * 0.2;
        }

        // 计算风险等级
        $riskLevel = $this->calculateRiskLevel($riskScore);

        $details = [
            'anomalies' => $anomalies,
            'risk_score' => $riskScore,
            'user_id' => $userId,
            'ip' => $ip,
        ];

        if (RiskLevel::LOW !== $riskLevel) {
            $this->logger->warning('Abnormal pattern detected', [
                'detector' => $this->getName(),
                'details' => $details,
            ]);
        }

        $assessment = new RiskAssessment(
            $riskLevel->getScore(),
            $riskLevel,
            [] !== $anomalies ? ['patterns' => 'Abnormal patterns detected'] : ['patterns' => 'Normal patterns']
        );

        return new DetectionResult($assessment, $details);
    }

    /**
     * @return array{is_anomaly: bool, severity: float, request_count: int, threshold: int, type: string}
     */
    private function checkRequestRate(string $ip): array
    {
        $requestCount = $this->metricsCollector->getRequestCount($ip, '1m');
        $isAnomaly = $requestCount > self::NORMAL_REQUEST_RATE_PER_MINUTE;

        $severity = 0.0;
        if ($isAnomaly) {
            $severity = min(1.0, ($requestCount - self::NORMAL_REQUEST_RATE_PER_MINUTE) / self::NORMAL_REQUEST_RATE_PER_MINUTE);
        }

        return [
            'is_anomaly' => $isAnomaly,
            'severity' => $severity,
            'request_count' => $requestCount,
            'threshold' => self::NORMAL_REQUEST_RATE_PER_MINUTE,
            'type' => 'high_request_rate',
        ];
    }

    /**
     * @return array{is_anomaly: bool, severity: float, unique_paths: int, threshold: int, type: string}
     */
    private function checkPathDiversity(string $userId): array
    {
        $uniquePaths = $this->metricsCollector->getUniquePathsCount($userId, '1h');
        $isAnomaly = $uniquePaths > self::NORMAL_UNIQUE_PATHS_PER_HOUR;

        $severity = 0.0;
        if ($isAnomaly) {
            $severity = min(1.0, ($uniquePaths - self::NORMAL_UNIQUE_PATHS_PER_HOUR) / self::NORMAL_UNIQUE_PATHS_PER_HOUR);
        }

        return [
            'is_anomaly' => $isAnomaly,
            'severity' => $severity,
            'unique_paths' => $uniquePaths,
            'threshold' => self::NORMAL_UNIQUE_PATHS_PER_HOUR,
            'type' => 'high_path_diversity',
        ];
    }

    /**
     * @return array{is_anomaly: bool, severity: float, error_rate?: float, error_count?: int, total_requests?: int, threshold?: float, type: string}
     */
    private function checkErrorRate(string $userId): array
    {
        $totalRequests = $this->metricsCollector->getRequestCount($userId, '1h');

        if ($totalRequests < self::MIN_REQUESTS_FOR_ANALYSIS) {
            return ['is_anomaly' => false, 'severity' => 0.0, 'type' => 'insufficient_data'];
        }

        $errorCount = $this->metricsCollector->getErrorCount($userId, '1h');
        $errorRate = $errorCount / $totalRequests;
        $isAnomaly = $errorRate > self::NORMAL_ERROR_RATE;

        $severity = 0.0;
        if ($isAnomaly) {
            $severity = min(1.0, ($errorRate - self::NORMAL_ERROR_RATE) / self::NORMAL_ERROR_RATE);
        }

        return [
            'is_anomaly' => $isAnomaly,
            'severity' => $severity,
            'error_rate' => $errorRate,
            'error_count' => $errorCount,
            'total_requests' => $totalRequests,
            'threshold' => self::NORMAL_ERROR_RATE,
            'type' => 'high_error_rate',
        ];
    }

    /**
     * @return array{is_anomaly: bool, severity: float, night_activity_ratio?: float, threshold?: float, type: string}
     */
    private function checkTimePattern(string $userId): array
    {
        $currentHour = (int) date('G');
        $isNightTime = $currentHour >= 2 && $currentHour <= 6;

        // 检查是否在异常时间段有大量活动
        if ($isNightTime) {
            $nightRequests = $this->metricsCollector->getRequestCount($userId, '30m');
            $isAnomaly = $nightRequests > 10;

            return [
                'is_anomaly' => $isAnomaly,
                'severity' => $isAnomaly ? 0.6 : 0.0,
                'current_hour' => $currentHour,
                'request_count' => $nightRequests,
                'type' => 'night_time_activity',
            ];
        }

        return ['is_anomaly' => false, 'severity' => 0.0, 'type' => 'normal_hours'];
    }

    private function calculateRiskLevel(float $riskScore): RiskLevel
    {
        if ($riskScore >= 0.7) {
            return RiskLevel::HIGH;
        }
        if ($riskScore >= 0.4) {
            return RiskLevel::MEDIUM;
        }

        return RiskLevel::LOW;
    }
}
