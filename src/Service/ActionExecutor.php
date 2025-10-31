<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\AntiFraudBundle\Entity\DetectionLog;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\DetectionResult;
use Tourze\AntiFraudBundle\Model\RiskAssessment;
use Tourze\AntiFraudBundle\Rule\Action\ActionInterface;

/**
 * 动作执行器
 * 统一处理各种响应动作，并记录执行日志
 */
#[WithMonologChannel(channel: 'anti_fraud')]
#[Autoconfigure(public: true)]
class ActionExecutor
{
    /** @var array<int, array{action: ActionInterface, context: Context, assessment: RiskAssessment, response: ?Response, executed_at: \DateTimeImmutable, duration: float}> */
    private array $executedActions = [];

    public function __construct(
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
    ) {
    }

    public function execute(
        ActionInterface $action,
        Request $request,
        Context $context,
        RiskAssessment $assessment,
    ): ?Response {
        $startTime = microtime(true);

        try {
            // 执行动作
            $response = $action->execute($request);

            // 记录执行结果
            $this->logExecution($action, $context, $assessment, true, null, $response);

            // 记录到内存，用于后续分析
            $this->executedActions[] = [
                'action' => $action,
                'context' => $context,
                'assessment' => $assessment,
                'response' => $response,
                'executed_at' => new \DateTimeImmutable(),
                'duration' => microtime(true) - $startTime,
            ];

            return $response;
        } catch (\Exception $e) {
            $this->logger->error('Action execution failed', [
                'action' => get_class($action),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 记录失败
            $this->logExecution($action, $context, $assessment, false, $e->getMessage());

            // 不要因为动作执行失败而影响正常请求
            return null;
        }
    }

    /**
     * @param array<ActionInterface> $actions
     */
    public function executeMultiple(
        array $actions,
        Request $request,
        Context $context,
        RiskAssessment $assessment,
    ): ?Response {
        /** @var Response[] $responses */
        $responses = [];

        foreach ($actions as $action) {
            if (!$action instanceof ActionInterface) {
                continue;
            }

            $response = $this->execute($action, $request, $context, $assessment);

            // 如果有阻断性响应，立即返回
            if (null !== $response && $response->getStatusCode() >= 400) {
                return $response;
            }

            if (null !== $response) {
                $responses[] = $response;
            }
        }

        // 返回第一个非空响应，如果没有则返回null
        return $responses[0] ?? null;
    }

    private function logExecution(
        ActionInterface $action,
        Context $context,
        RiskAssessment $assessment,
        bool $success,
        ?string $error = null,
        ?Response $response = null,
    ): void {
        $log = new DetectionLog();
        $log->setUserId($context->getUserId());
        $log->setSessionId($context->getSessionId());
        $log->setIpAddress($context->getIp());
        $log->setUserAgent($context->getUserAgent());
        $actionName = $context->getAttribute('action', 'unknown');
        if (!is_string($actionName)) {
            $actionName = 'unknown';
        }
        $log->setAction($actionName);
        $log->setRiskLevel($assessment->getRiskLevel());
        $log->setRiskScore($assessment->getRiskScore());

        // 设置匹配的规则
        /** @var array<string, mixed> $matchedRules */
        $matchedRules = [];
        foreach ($assessment->getDetectionResults() as $detectorName => $result) {
            if ($result instanceof DetectionResult && RiskLevel::LOW !== $result->getRiskLevel()) {
                $matchedRules[$detectorName] = [
                    'detector' => $detectorName,
                    'risk_level' => $result->getRiskLevel()->value,
                    'risk_score' => $assessment->getRiskScore(),
                ];
            }
        }
        $log->setMatchedRules($matchedRules);

        // 设置检测详情
        /** @var array<string, mixed> $detectionDetails */
        $detectionDetails = [
            'assessment_details' => $assessment->getDetails(),
            'action_executed' => get_class($action),
            'action_success' => $success,
            'action_error' => $error,
        ];

        if (null !== $response) {
            $detectionDetails['response_status'] = $response->getStatusCode();
        }

        $log->setDetectionDetails($detectionDetails);

        // 设置执行的动作
        $log->setActionTaken($action->getName());

        // 设置请求信息
        $log->setRequestPath($context->getPath());
        $log->setRequestMethod($context->getMethod());

        // 设置额外信息
        $log->setCountryCode($context->getIpCountry());
        $log->setIsProxy($context->isProxyIp());
        $isBot = $context->getAttribute('is_bot', false);
        $log->setIsBot(is_bool($isBot) ? $isBot : false);

        // 设置响应时间
        $responseTime = $context->getAttribute('response_time');
        if (null !== $responseTime && is_numeric($responseTime)) {
            $log->setResponseTime((int) $responseTime);
        }

        // 保存日志
        try {
            $this->entityManager->persist($log);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->logger->error('Failed to save detection log', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @return array<int, array{action: ActionInterface, context: Context, assessment: RiskAssessment, response: ?Response, executed_at: \DateTimeImmutable, duration: float}>
     */
    public function getExecutedActions(): array
    {
        return $this->executedActions;
    }

    public function clearExecutedActions(): void
    {
        $this->executedActions = [];
    }

    /**
     * 获取特定类型动作的执行统计
     * @return array<string, array{count: int, total_duration: float, avg_duration: float}>
     */
    public function getActionStatistics(): array
    {
        /** @var array<string, array{count: int, total_duration: float, avg_duration: float}> $stats */
        $stats = [];

        foreach ($this->executedActions as $execution) {
            $actionName = method_exists($execution['action'], 'getName')
                ? $execution['action']->getName()
                : 'unknown';

            if (!isset($stats[$actionName])) {
                $stats[$actionName] = [
                    'count' => 0,
                    'total_duration' => 0,
                    'avg_duration' => 0,
                ];
            }

            ++$stats[$actionName]['count'];
            $stats[$actionName]['total_duration'] += $execution['duration'];
            $stats[$actionName]['avg_duration'] =
                $stats[$actionName]['total_duration'] / $stats[$actionName]['count'];
        }

        return $stats;
    }
}
