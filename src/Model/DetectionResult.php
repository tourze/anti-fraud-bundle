<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Model;

use Psr\Log\NullLogger;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Rule\Action\ActionInterface;
use Tourze\AntiFraudBundle\Rule\Action\LogAction;
use Tourze\AntiFraudBundle\Rule\Rule;

class DetectionResult
{
    private ?RiskAssessment $riskAssessment = null;

    /** @var array<string, mixed> */
    private array $metadata = [];

    private RiskLevel $riskLevel;

    private ActionInterface $action;

    /** @var array<int, Rule> */
    private array $matchedRules;

    /** @var array<string, mixed> */
    private array $details = [];

    /**
     * @param array<int, Rule> $matchedRules
     * @param array<string, mixed> $actionOrMetadata
     * @param array<string, mixed> $details
     */
    public function __construct(
        RiskLevel|RiskAssessment $riskLevelOrAssessment,
        ActionInterface|array $actionOrMetadata,
        array $matchedRules = [],
        array $details = [],
    ) {
        if ($riskLevelOrAssessment instanceof RiskAssessment) {
            // New style constructor: (RiskAssessment, array metadata)
            $this->riskAssessment = $riskLevelOrAssessment;
            $this->riskLevel = $riskLevelOrAssessment->getRiskLevel();
            if (is_array($actionOrMetadata)) {
                $this->metadata = $actionOrMetadata;
            }
            $this->action = new LogAction(new NullLogger());
            $this->matchedRules = $matchedRules;
            $this->details = $details;
        } else {
            // Old style constructor: (RiskLevel, ActionInterface, array rules, array details)
            $this->riskLevel = $riskLevelOrAssessment;
            if ($actionOrMetadata instanceof ActionInterface) {
                $this->action = $actionOrMetadata;
            }
            $this->matchedRules = $matchedRules;
            $this->details = $details;
        }
    }

    public function getRiskLevel(): RiskLevel
    {
        return $this->riskLevel;
    }

    public function getRiskAssessment(): ?RiskAssessment
    {
        return $this->riskAssessment;
    }

    /**
     * @return array<string, mixed>
     */
    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function getAction(): ActionInterface
    {
        return $this->action;
    }

    /**
     * @return Rule[]
     */
    public function getMatchedRules(): array
    {
        return $this->matchedRules;
    }

    public function shouldTakeAction(): bool
    {
        return $this->riskLevel->isHigherThan(RiskLevel::MEDIUM);
    }

    public function hasMatchedRules(): bool
    {
        return count($this->matchedRules) > 0;
    }

    /**
     * @return string[]
     */
    public function getMatchedRuleNames(): array
    {
        return array_map(
            fn (Rule $rule) => $rule->getName(),
            $this->matchedRules
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getDetails(): array
    {
        return $this->details;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'riskLevel' => $this->riskLevel->value,
            'action' => $this->action->getName(),
            'matchedRules' => $this->getMatchedRuleNames(),
            'shouldTakeAction' => $this->shouldTakeAction(),
            'details' => $this->details,
        ];
    }
}
