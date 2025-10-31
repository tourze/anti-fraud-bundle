<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Detector;

use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\DetectionResult;
use Tourze\AntiFraudBundle\Model\RiskAssessment;
use Tourze\AntiFraudBundle\Service\MetricsCollector;

class IpRateLimitDetector extends AbstractDetector
{
    private const DEFAULT_HIGH_THRESHOLD = 60;
    private const DEFAULT_CRITICAL_THRESHOLD = 100;

    public function __construct(
        private MetricsCollector $metricsCollector,
        bool $enabled = true,
        private int $highThreshold = self::DEFAULT_HIGH_THRESHOLD,
        private int $criticalThreshold = self::DEFAULT_CRITICAL_THRESHOLD,
    ) {
        parent::__construct('ip-rate-limit', $enabled);
    }

    protected function performDetection(Context $context): DetectionResult
    {
        $ip = $context->getIp();
        $requestCount = $this->metricsCollector->getRequestCount($ip, '1m');

        $metadata = [
            'ip' => $ip,
            'request_count_1m' => $requestCount,
            'high_threshold' => $this->highThreshold,
            'critical_threshold' => $this->criticalThreshold,
        ];

        if ($requestCount >= $this->criticalThreshold) {
            $assessment = new RiskAssessment(
                RiskLevel::CRITICAL->getScore(),
                RiskLevel::CRITICAL,
                [],
                ['reasons' => ["Critical request rate detected: {$requestCount} requests in last minute"]]
            );
        } elseif ($requestCount >= $this->highThreshold) {
            $assessment = new RiskAssessment(
                RiskLevel::HIGH->getScore(),
                RiskLevel::HIGH,
                [],
                ['reasons' => ["High request rate detected: {$requestCount} requests in last minute"]]
            );
        } else {
            $assessment = new RiskAssessment(
                RiskLevel::LOW->getScore(),
                RiskLevel::LOW,
                [],
                ['reasons' => ["Normal request rate: {$requestCount} requests in last minute"]]
            );
        }

        return new DetectionResult($assessment, $metadata);
    }
}
