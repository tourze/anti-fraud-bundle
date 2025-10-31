<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Rule\Engine;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Contracts\Cache\CacheInterface;
use Tourze\AntiFraudBundle\Contract\Rule\RuleEngineInterface;
use Tourze\AntiFraudBundle\Entity\Rule;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\DetectionResult;
use Tourze\AntiFraudBundle\Repository\RuleRepository;
use Tourze\AntiFraudBundle\Rule\Action\ActionInterface;
use Tourze\AntiFraudBundle\Rule\Action\BlockAction;
use Tourze\AntiFraudBundle\Rule\Action\LogAction;
use Tourze\AntiFraudBundle\Rule\Action\ThrottleAction;
use Tourze\AntiFraudBundle\Rule\Rule as RuleModel;

#[WithMonologChannel(channel: 'anti_fraud')]
#[Autoconfigure(public: true)]
class DynamicRuleEngine implements RuleEngineInterface
{
    /** @var Rule[] */
    private array $defaultRules = [];

    public function __construct(
        private RuleRepository $repository,
        private RuleEvaluator $evaluator,
        private CacheInterface $cache,
        private LoggerInterface $logger = new NullLogger(),
    ) {
        $this->loadDefaultRules();
    }

    public function evaluate(Context $context): DetectionResult
    {
        $rules = $this->getActiveRules();
        $matchedRules = $this->findMatchingRules($rules, $context);

        $highestPriority = $this->findHighestPriorityRule($matchedRules);

        if (null !== $highestPriority) {
            return $this->createResultFromRule($highestPriority, $matchedRules);
        }

        // No rules matched, return default low risk
        return new DetectionResult(RiskLevel::LOW, new LogAction($this->logger), []);
    }

    /**
     * @param Rule[] $rules
     * @return Rule[]
     */
    private function findMatchingRules(array $rules, Context $context): array
    {
        $matchedRules = [];

        foreach ($rules as $rule) {
            if ($this->evaluator->matches($rule, $context)) {
                $matchedRules[] = $rule;

                // If terminal rule, stop evaluation
                if ($rule->isTerminal()) {
                    break;
                }
            }
        }

        return $matchedRules;
    }

    /**
     * @param Rule[] $matchedRules
     */
    private function findHighestPriorityRule(array $matchedRules): ?Rule
    {
        $highestPriority = null;

        foreach ($matchedRules as $rule) {
            if (null === $highestPriority || $rule->getPriority() > $highestPriority->getPriority()) {
                $highestPriority = $rule;
            }
        }

        return $highestPriority;
    }

    /**
     * @param Rule[] $matchedRules
     */
    private function createResultFromRule(Rule $rule, array $matchedRules): DetectionResult
    {
        $actions = $rule->getActions();

        // actions is always an array for Entity Rule
        $primaryAction = $this->createActionFromArray([] !== $actions ? $actions : ['type' => 'log']);

        // Convert Entity\Rule to Rule\Rule for DetectionResult
        $ruleObjects = array_values(array_map(
            fn (Rule $entityRule) => RuleModel::fromEntity($entityRule),
            $matchedRules
        ));

        return new DetectionResult(
            $rule->getRiskLevel(),
            $primaryAction,
            $ruleObjects
        );
    }

    public function refreshRules(): void
    {
        $this->cache->delete('antifraud.rules');
    }

    /**
     * @return Rule[]
     */
    private function getActiveRules(): array
    {
        return $this->cache->get('antifraud.rules', function () {
            $customRules = $this->repository->findActiveRules();

            return array_merge($this->defaultRules, $customRules);
        });
    }

    /**
     * @param array<string, mixed> $actionData
     */
    private function createActionFromArray(array $actionData): ActionInterface
    {
        $type = $actionData['type'] ?? 'log';

        return match ($type) {
            'block' => new BlockAction(
                is_string($actionData['message'] ?? null) ? $actionData['message'] : 'Access denied'
            ),
            'throttle' => new ThrottleAction(
                is_int($actionData['delay'] ?? null) ? $actionData['delay'] : 60
            ),
            'log' => new LogAction(
                $this->logger,
                is_string($actionData['level'] ?? null) ? $actionData['level'] : 'info',
                is_string($actionData['message'] ?? null) ? $actionData['message'] : 'Anti-fraud detection triggered'
            ),
            default => new LogAction($this->logger),
        };
    }

    private function loadDefaultRules(): void
    {
        $this->defaultRules = [
            (function () {
                $rule = new Rule();
                $rule->setName('rate_limit_login');
                $rule->setCondition('request["path"] == "/login" and request_count("5m") > 5');
                $rule->setRiskLevel(RiskLevel::HIGH);
                $rule->setActions(['type' => 'block', 'message' => 'Too many login attempts']);
                $rule->setPriority(100);

                return $rule;
            })(),
            (function () {
                $rule = new Rule();
                $rule->setName('rate_limit_api');
                $rule->setCondition('request["path"] starts with "/api/" and request_count("1m") > 100');
                $rule->setRiskLevel(RiskLevel::MEDIUM);
                $rule->setActions(['type' => 'throttle', 'delay' => 60]);
                $rule->setPriority(90);

                return $rule;
            })(),
            (function () {
                $rule = new Rule();
                $rule->setName('suspicious_user_agent');
                $rule->setCondition('request["user_agent"] matches "/(bot|crawler|spider)/i" and not (request["path"] == "/robots.txt")');
                $rule->setRiskLevel(RiskLevel::MEDIUM);
                $rule->setActions(['type' => 'log', 'level' => 'warning', 'message' => 'Suspicious user agent detected']);
                $rule->setPriority(80);

                return $rule;
            })(),
            (function () {
                $rule = new Rule();
                $rule->setName('proxy_ip_detected');
                $rule->setCondition('ip["is_proxy"] == true');
                $rule->setRiskLevel(RiskLevel::MEDIUM);
                $rule->setActions(['type' => 'log', 'level' => 'warning', 'message' => 'Proxy IP detected']);
                $rule->setPriority(70);

                return $rule;
            })(),
        ];
    }
}
