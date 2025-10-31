<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Detector\Account;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\AntiFraudBundle\Detector\AbstractDetector;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\DetectionResult;
use Tourze\AntiFraudBundle\Rule\Action\LogAction;
use Tourze\AntiFraudBundle\Rule\Action\ThrottleAction;

/**
 * 代理/VPN检测器
 * 检测用户是否使用代理服务器或VPN连接
 */
#[WithMonologChannel(channel: 'anti_fraud')]
class ProxyDetector extends AbstractDetector
{
    // 已知的代理/VPN服务提供商的IP段
    private const KNOWN_PROXY_RANGES = [
        '10.0.0.0/8',
        '172.16.0.0/12',
        '192.168.0.0/16',
        // 实际应用中这里应该包含真实的VPN服务商IP段
    ];

    // 可疑的端口号
    private const SUSPICIOUS_PORTS = [1080, 3128, 8080, 8888];

    // 可疑的HTTP头
    private const PROXY_HEADERS = [
        'HTTP_VIA',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED',
        'HTTP_CLIENT_IP',
        'HTTP_FORWARDED_FOR_IP',
        'VIA',
        'X_FORWARDED_FOR',
        'FORWARDED_FOR',
        'X_FORWARDED',
        'FORWARDED',
        'CLIENT_IP',
        'FORWARDED_FOR_IP',
        'HTTP_PROXY_CONNECTION',
    ];

    public function __construct(
        private LoggerInterface $logger,
    ) {
        parent::__construct('proxy_detector');
    }

    protected function performDetection(Context $context): DetectionResult
    {
        $ip = $context->getIp();
        $typedHeaders = $this->normalizeHeaders($context->getAttribute('headers', []));

        $proxyData = $this->analyzeProxyIndicators($context, $ip, $typedHeaders);
        $riskScore = $proxyData['risk_score'] ?? 0.0;
        if (!is_float($riskScore)) {
            $riskScore = 0.0;
        }
        $riskLevel = $this->calculateRiskLevel($riskScore);
        $action = $this->selectAction($riskLevel);

        $this->logDetectionResult($riskLevel, $proxyData);

        return new DetectionResult(
            $riskLevel,
            $action,
            [],
            $proxyData
        );
    }

    /**
     * 标准化请求头格式
     * @param mixed $headers
     * @return array<string, mixed>
     */
    private function normalizeHeaders($headers): array
    {
        if (!is_array($headers)) {
            return [];
        }

        $typedHeaders = [];
        foreach ($headers as $key => $value) {
            if (is_string($key)) {
                $typedHeaders[$key] = $value;
            }
        }

        return $typedHeaders;
    }

    /**
     * 分析代理指标
     * @param array<string, mixed> $headers
     * @return array<string, mixed>
     */
    private function analyzeProxyIndicators(Context $context, string $ip, array $headers): array
    {
        $proxyIndicators = [];
        $riskScore = 0.0;

        // 检查HTTP头
        $headerCheck = $this->checkProxyHeaders($headers);
        if ($headerCheck['detected']) {
            $proxyIndicators['proxy_headers'] = $headerCheck['headers'];
            $riskScore += 0.5;
        }

        // 检查IP是否在已知代理范围内
        if ($this->isInProxyRange($ip)) {
            $proxyIndicators['ip_in_proxy_range'] = true;
            $riskScore += 0.3;
        }

        // 检查端口（如果有）
        $port = $context->getAttribute('remote_port');
        if (is_numeric($port) && $this->isSuspiciousPort((int) $port)) {
            $proxyIndicators['suspicious_port'] = $port;
            $riskScore += 0.2;
        }

        // 检查是否已标记为代理
        if ($context->isProxyIp()) {
            $proxyIndicators['marked_as_proxy'] = true;
            $riskScore += 0.8;
        }

        return [
            'proxy_indicators' => $proxyIndicators,
            'risk_score' => $riskScore,
            'ip' => $ip,
        ];
    }

    /**
     * 选择相应的行动策略
     */
    private function selectAction(RiskLevel $riskLevel): LogAction|ThrottleAction
    {
        return RiskLevel::LOW === $riskLevel
            ? new LogAction($this->logger, 'info', 'Proxy check completed')
            : new ThrottleAction(5);
    }

    /**
     * 记录检测结果
     * @param array<string, mixed> $proxyData
     */
    private function logDetectionResult(RiskLevel $riskLevel, array $proxyData): void
    {
        if (RiskLevel::LOW !== $riskLevel) {
            $this->logger->warning('Proxy/VPN detected', [
                'detector' => $this->getName(),
                'details' => $proxyData,
            ]);
        }
    }

    /**
     * @param array<string, mixed> $headers
     * @return array{detected: bool, headers: array<string, mixed>}
     */
    private function checkProxyHeaders(array $headers): array
    {
        $detectedHeaders = [];

        foreach (self::PROXY_HEADERS as $header) {
            $headerLower = strtolower($header);
            foreach ($headers as $key => $value) {
                if (strtolower($key) === $headerLower && '' !== $value && null !== $value) {
                    $detectedHeaders[$key] = $value;
                }
            }
        }

        return [
            'detected' => [] !== $detectedHeaders,
            'headers' => $detectedHeaders,
        ];
    }

    private function isInProxyRange(string $ip): bool
    {
        foreach (self::KNOWN_PROXY_RANGES as $range) {
            if ($this->ipInRange($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    private function ipInRange(string $ip, string $range): bool
    {
        [$subnet, $bits] = explode('/', $range);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - (int) $bits);
        $subnet &= $mask;

        return ($ip & $mask) === $subnet;
    }

    private function isSuspiciousPort(int $port): bool
    {
        return in_array($port, self::SUSPICIOUS_PORTS, true);
    }

    private function calculateRiskLevel(float $riskScore): RiskLevel
    {
        if ($riskScore >= 0.8) {
            return RiskLevel::HIGH;
        }
        if ($riskScore >= 0.5) {
            return RiskLevel::MEDIUM;
        }

        return RiskLevel::LOW;
    }
}
