<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Detector\Account;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\AntiFraudBundle\Contract\DetectorInterface;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\DetectionResult;
use Tourze\AntiFraudBundle\Repository\DetectionLogRepository;
use Tourze\AntiFraudBundle\Rule\Action\LogAction;
use Tourze\AntiFraudBundle\Service\MetricsCollector;

/**
 * 多账号检测器
 * 检测同一设备或IP是否存在多个账号登录的情况
 */
#[WithMonologChannel(channel: 'anti_fraud')]
#[Autoconfigure(public: true)]
class MultiAccountDetector implements DetectorInterface
{
    private const MAX_ACCOUNTS_PER_IP = 5;
    private const MAX_ACCOUNTS_PER_DEVICE = 3;
    private const TIME_WINDOW = 3600; // 1 hour

    public function __construct(
        private MetricsCollector $metricsCollector,
        private DetectionLogRepository $logRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function getName(): string
    {
        return 'multi_account_detector';
    }

    public function detect(Context $context): DetectionResult
    {
        $ip = $context->getIp();
        $deviceFingerprint = $context->getAttribute('device_fingerprint');
        $userId = $context->getUserId();

        // 检查IP关联的账号数
        $ipAccountCount = $this->getUniqueAccountsByIp($ip);

        // 检查设备关联的账号数（如果有设备指纹）
        $deviceAccountCount = 0;
        if (is_string($deviceFingerprint) && '' !== $deviceFingerprint) {
            $deviceAccountCount = $this->getUniqueAccountsByDevice($deviceFingerprint);
        }

        // 计算风险等级
        $riskLevel = $this->calculateRiskLevel($ipAccountCount, $deviceAccountCount);

        // 记录检测结果
        $details = [
            'ip_account_count' => $ipAccountCount,
            'device_account_count' => $deviceAccountCount,
            'max_allowed_per_ip' => self::MAX_ACCOUNTS_PER_IP,
            'max_allowed_per_device' => self::MAX_ACCOUNTS_PER_DEVICE,
        ];

        if (RiskLevel::LOW !== $riskLevel) {
            $this->logger->warning('Multiple accounts detected', [
                'detector' => $this->getName(),
                'user_id' => $userId,
                'ip' => $ip,
                'details' => $details,
            ]);
        }

        $action = new LogAction($this->logger, 'info', 'Multi-account detection completed');

        return new DetectionResult(
            $riskLevel,
            $action,
            [],
            $details
        );
    }

    private function getUniqueAccountsByIp(string $ip): int
    {
        $since = new \DateTimeImmutable(sprintf('-%d seconds', self::TIME_WINDOW));
        $logs = $this->logRepository->findByIpAddress($ip, 1000);

        $uniqueUsers = [];
        foreach ($logs as $log) {
            if ($log->getCreatedAt() >= $since) {
                $uniqueUsers[$log->getUserId()] = true;
            }
        }

        return count($uniqueUsers);
    }

    private function getUniqueAccountsByDevice(string $deviceFingerprint): int
    {
        // 这里应该查询设备指纹相关的日志
        // 暂时简化处理，返回基于metrics的计数
        return $this->metricsCollector->getUniqueUsersCount($deviceFingerprint, 'device', self::TIME_WINDOW);
    }

    private function calculateRiskLevel(int $ipAccountCount, int $deviceAccountCount): RiskLevel
    {
        // 设备指纹更可靠，优先考虑
        if ($deviceAccountCount > 0) {
            if ($deviceAccountCount > self::MAX_ACCOUNTS_PER_DEVICE * 2) {
                return RiskLevel::CRITICAL;
            }
            if ($deviceAccountCount > self::MAX_ACCOUNTS_PER_DEVICE) {
                return RiskLevel::HIGH;
            }
        }

        // IP检测
        if ($ipAccountCount > self::MAX_ACCOUNTS_PER_IP * 2) {
            return RiskLevel::HIGH;
        }
        if ($ipAccountCount > self::MAX_ACCOUNTS_PER_IP) {
            return RiskLevel::MEDIUM;
        }

        return RiskLevel::LOW;
    }

    public function isEnabled(): bool
    {
        return ($_ENV['ANTIFRAUD_MULTI_ACCOUNT_DETECTION'] ?? 'true') !== 'false';
    }
}
