<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Detector;

use Tourze\AntiFraudBundle\Contract\DetectorInterface;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\DetectionResult;
use Tourze\AntiFraudBundle\Model\RiskAssessment;

abstract class AbstractDetector implements DetectorInterface
{
    protected string $name;

    protected bool $enabled;

    public function __construct(string $name, bool $enabled = true)
    {
        $this->name = $name;
        $this->enabled = $enabled;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function detect(Context $context): DetectionResult
    {
        if (!$this->enabled) {
            $assessment = new RiskAssessment(0.0, RiskLevel::LOW, [], ['reasons' => ['Detector disabled']]);

            return new DetectionResult($assessment, []);
        }

        return $this->performDetection($context);
    }

    abstract protected function performDetection(Context $context): DetectionResult;
}
