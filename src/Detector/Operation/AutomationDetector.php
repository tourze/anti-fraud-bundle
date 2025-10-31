<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Detector\Operation;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\AntiFraudBundle\Detector\AbstractDetector;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\DetectionResult;
use Tourze\AntiFraudBundle\Model\RiskAssessment;
use Tourze\AntiFraudBundle\Service\MetricsCollector;

/**
 * 自动化脚本检测器
 * 检测是否存在自动化脚本或机器人行为
 */
#[WithMonologChannel(channel: 'anti_fraud')]
class AutomationDetector extends AbstractDetector
{
    // 自动化行为阈值
    private const MIN_HUMAN_RESPONSE_TIME = 100; // 毫秒
    private const MAX_ACTIONS_PER_SECOND = 3;
    private const PERFECT_TIMING_THRESHOLD = 0.95; // 时间间隔一致性阈值
    private const MIN_SAMPLES_FOR_ANALYSIS = 5;

    // 可疑的User-Agent模式
    private const BOT_USER_AGENT_PATTERNS = [
        '/bot/i',
        '/crawler/i',
        '/spider/i',
        '/scraper/i',
        '/curl/i',
        '/wget/i',
        '/python-requests/i',
        '/postman/i',
        '/insomnia/i',
        '/axios/i',
        '/fetch/i',
    ];

    public function __construct(
        private MetricsCollector $metricsCollector,
        private LoggerInterface $logger,
    ) {
        parent::__construct('automation_detector');
    }

    protected function performDetection(Context $context): DetectionResult
    {
        $userId = $context->getUserId();
        $sessionId = $context->getSessionId();
        $userAgent = $context->getUserAgent();

        $automationIndicators = [];
        $riskScore = 0.0;

        // 检查User-Agent
        $userAgentCheck = $this->checkUserAgent($userAgent);
        if ($userAgentCheck['is_bot']) {
            $automationIndicators['bot_user_agent'] = $userAgentCheck;
            $riskScore += 0.5;
        }

        // 检查响应时间
        $responseTimeCheck = $this->checkResponseTime($context);
        if ($responseTimeCheck['is_suspicious']) {
            $automationIndicators['inhuman_response_time'] = $responseTimeCheck;
            $riskScore += 0.4;
        }

        // 检查操作频率
        $frequencyCheck = $this->checkActionFrequency($sessionId);
        if ($frequencyCheck['is_suspicious']) {
            $automationIndicators['high_frequency'] = $frequencyCheck;
            $riskScore += 0.4;
        }

        // 检查时间模式
        $timingCheck = $this->checkTimingPatterns($sessionId);
        if ($timingCheck['is_suspicious']) {
            $automationIndicators['perfect_timing'] = $timingCheck;
            $riskScore += 0.6;
        }

        // 检查JavaScript执行
        $jsCheck = $this->checkJavaScriptExecution($context);
        if ($jsCheck['is_suspicious']) {
            $automationIndicators['no_javascript'] = $jsCheck;
            $riskScore += 0.3;
        }

        // 检查鼠标/键盘事件
        $interactionCheck = $this->checkUserInteraction($context);
        if ($interactionCheck['is_suspicious']) {
            $automationIndicators['no_user_interaction'] = $interactionCheck;
            $riskScore += 0.5;
        }

        $riskLevel = $this->calculateRiskLevel($riskScore);

        $details = [
            'automation_indicators' => $automationIndicators,
            'risk_score' => $riskScore,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'user_agent' => $userAgent,
        ];

        if (RiskLevel::LOW !== $riskLevel) {
            $this->logger->warning('Automation detected', [
                'detector' => $this->getName(),
                'details' => $details,
            ]);
        }

        $assessment = new RiskAssessment(
            $riskLevel->getScore(),
            $riskLevel,
            count($automationIndicators) > 0 ? ['detection' => 'Automation detected'] : ['detection' => 'No automation detected']
        );

        return new DetectionResult($assessment, $details);
    }

    /**
     * @return array{is_bot: bool, matched_pattern?: string, user_agent?: string}
     */
    private function checkUserAgent(string $userAgent): array
    {
        foreach (self::BOT_USER_AGENT_PATTERNS as $pattern) {
            if (1 === preg_match($pattern, $userAgent)) {
                return [
                    'is_bot' => true,
                    'matched_pattern' => $pattern,
                    'user_agent' => $userAgent,
                ];
            }
        }

        // 检查是否缺少User-Agent
        if ('' === $userAgent) {
            return [
                'is_bot' => true,
                'reason' => 'empty_user_agent',
            ];
        }

        // 检查是否是常见浏览器
        $commonBrowsers = ['Chrome', 'Firefox', 'Safari', 'Edge', 'Opera'];
        $hasCommonBrowser = false;
        foreach ($commonBrowsers as $browser) {
            if (false !== stripos($userAgent, $browser)) {
                $hasCommonBrowser = true;
                break;
            }
        }

        if (!$hasCommonBrowser) {
            return [
                'is_bot' => true,
                'reason' => 'uncommon_browser',
                'user_agent' => $userAgent,
            ];
        }

        return ['is_bot' => false];
    }

    /**
     * @return array{is_suspicious: bool, response_time?: float, threshold?: float, reason?: string}
     */
    private function checkResponseTime(Context $context): array
    {
        $responseTime = $context->getAttribute('response_time', null);

        if (null === $responseTime) {
            return ['is_suspicious' => false, 'reason' => 'no_data'];
        }

        // 确保 response_time 是数值类型
        if (!is_numeric($responseTime)) {
            return ['is_suspicious' => false, 'reason' => 'invalid_data'];
        }
        $responseTime = (float) $responseTime;

        // 响应时间太快，可能是自动化
        if ($responseTime < self::MIN_HUMAN_RESPONSE_TIME) {
            return [
                'is_suspicious' => true,
                'response_time' => $responseTime,
                'threshold' => (float) self::MIN_HUMAN_RESPONSE_TIME,
                'reason' => 'too_fast',
            ];
        }

        return ['is_suspicious' => false];
    }

    /**
     * @return array{is_suspicious: bool, frequency?: float, threshold?: float}
     */
    private function checkActionFrequency(string $sessionId): array
    {
        $actionsPerSecond = $this->metricsCollector->getActionsPerSecond($sessionId);

        if ($actionsPerSecond > self::MAX_ACTIONS_PER_SECOND) {
            return [
                'is_suspicious' => true,
                'actions_per_second' => $actionsPerSecond,
                'threshold' => self::MAX_ACTIONS_PER_SECOND,
            ];
        }

        return ['is_suspicious' => false];
    }

    /**
     * @return array{is_suspicious: bool, patterns?: array<string, mixed>}
     */
    private function checkTimingPatterns(string $sessionId): array
    {
        $timings = $this->metricsCollector->getActionTimings($sessionId, 20);

        if (count($timings) < self::MIN_SAMPLES_FOR_ANALYSIS) {
            return ['is_suspicious' => false, 'reason' => 'insufficient_data'];
        }

        // 计算时间间隔
        $intervals = [];
        for ($i = 1; $i < count($timings); ++$i) {
            $intervals[] = $timings[$i] - $timings[$i - 1];
        }

        // 计算标准差
        $mean = array_sum($intervals) / count($intervals);
        $variance = 0;
        foreach ($intervals as $interval) {
            $variance += pow($interval - $mean, 2);
        }
        $stdDev = sqrt($variance / count($intervals));

        // 如果标准差很小，说明时间间隔非常一致，可能是自动化
        $consistency = $mean > 0 ? 1 - ($stdDev / $mean) : 0;

        if ($consistency > self::PERFECT_TIMING_THRESHOLD) {
            return [
                'is_suspicious' => true,
                'consistency' => $consistency,
                'threshold' => self::PERFECT_TIMING_THRESHOLD,
                'mean_interval' => $mean,
                'std_dev' => $stdDev,
            ];
        }

        return ['is_suspicious' => false];
    }

    /**
     * @return array{is_suspicious: bool, js_disabled?: bool, execution_time?: float}
     */
    private function checkJavaScriptExecution(Context $context): array
    {
        $jsToken = $context->getAttribute('js_token');
        $jsExecuted = $context->getAttribute('js_executed', false);

        // 如果没有执行JavaScript，可能是自动化工具
        if (false === $jsExecuted && null !== $jsToken) {
            return [
                'is_suspicious' => true,
                'js_disabled' => true,
                'expected_token' => $jsToken,
            ];
        }

        return ['is_suspicious' => false, 'js_disabled' => false];
    }

    /**
     * @return array{is_suspicious: bool, interaction_score?: float, details?: array<string, mixed>}
     */
    private function checkUserInteraction(Context $context): array
    {
        $mouseEvents = $context->getAttribute('mouse_events', 0);
        $keyboardEvents = $context->getAttribute('keyboard_events', 0);
        $touchEvents = $context->getAttribute('touch_events', 0);

        // 确保所有事件计数是数值类型
        if (!is_numeric($mouseEvents)) {
            $mouseEvents = 0;
        }
        if (!is_numeric($keyboardEvents)) {
            $keyboardEvents = 0;
        }
        if (!is_numeric($touchEvents)) {
            $touchEvents = 0;
        }

        $mouseEvents = (int) $mouseEvents;
        $keyboardEvents = (int) $keyboardEvents;
        $touchEvents = (int) $touchEvents;

        $totalInteractions = $mouseEvents + $keyboardEvents + $touchEvents;

        // 如果是表单提交但没有任何用户交互，可能是自动化
        $isFormSubmission = 'POST' === $context->getMethod();
        if ($isFormSubmission && 0 === $totalInteractions) {
            return [
                'is_suspicious' => true,
                'interaction_score' => 0.0,
                'details' => [
                    'mouse_events' => $mouseEvents,
                    'keyboard_events' => $keyboardEvents,
                    'touch_events' => $touchEvents,
                ],
            ];
        }

        return ['is_suspicious' => false, 'interaction_score' => 1.0];
    }

    private function calculateRiskLevel(float $riskScore): RiskLevel
    {
        if ($riskScore >= 1.2) {
            return RiskLevel::CRITICAL;
        }
        if ($riskScore >= 0.8) {
            return RiskLevel::HIGH;
        }
        if ($riskScore >= 0.4) {
            return RiskLevel::MEDIUM;
        }

        return RiskLevel::LOW;
    }
}
