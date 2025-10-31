<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Rule\Engine;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Tourze\AntiFraudBundle\Entity\Rule;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Service\MetricsCollector;

class RuleEvaluator
{
    private ExpressionLanguage $expressionLanguage;

    private ?Context $currentContext = null;

    public function __construct(
        private MetricsCollector $metricsCollector,
    ) {
        $this->expressionLanguage = new ExpressionLanguage();
        $this->registerFunctions();
    }

    public function matches(Rule $rule, Context $context): bool
    {
        // Skip disabled rules
        if (!$rule->isEnabled()) {
            return false;
        }

        try {
            // Store current context for custom functions
            $this->currentContext = $context;

            $variables = $this->prepareVariables($context);
            $result = $this->expressionLanguage->evaluate($rule->getCondition(), $variables);

            // Clear context
            $this->currentContext = null;

            return (bool) $result;
        } catch (\Exception $e) {
            // Rule parsing failed, log but don't affect request
            $this->currentContext = null;

            return false;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function prepareVariables(Context $context): array
    {
        $behavior = $context->getUserBehavior();

        return [
            'request' => [
                'path' => $context->getAttribute('path', $context->getPath()),
                'method' => $context->getMethod(),
                'user_agent' => $context->getUserAgent(),
                'ip' => $context->getIp(),
            ],
            'user' => [
                'id' => $context->getUserId(),
                'is_new' => $context->isNewUser(),
            ],
            'ip' => [
                'country' => $context->getIpCountry(),
                'is_proxy' => $context->isProxyIp(),
            ],
            'form' => [
                'submit_time' => $context->getFormSubmitTime(),
            ],
        ];
    }

    private function registerFunctions(): void
    {
        // Register custom function: request_count(time_window)
        $this->expressionLanguage->register(
            'request_count',
            function ($time) {
                if (is_string($time)) {
                    $timeString = $time;
                } elseif (is_numeric($time)) {
                    $timeString = (string) $time;
                } else {
                    $timeString = '0';
                }

                return sprintf('request_count("%s")', $timeString);
            },
            function ($arguments, $time) {
                if (null === $this->currentContext) {
                    return 0;
                }

                if (is_string($time)) {
                    $timeString = $time;
                } elseif (is_numeric($time)) {
                    $timeString = (string) $time;
                } else {
                    $timeString = '0';
                }

                return $this->metricsCollector->getRequestCount(
                    $this->currentContext->getIp(),
                    $timeString
                );
            }
        );
    }
}
