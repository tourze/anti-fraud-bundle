<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Detector;

use Tourze\AntiFraudBundle\Detector\AbstractDetector;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\DetectionResult;
use Tourze\AntiFraudBundle\Model\RiskAssessment;

class TestDetector extends AbstractDetector
{
    private bool $shouldDetect;

    private RiskLevel $riskLevel;

    public function __construct(bool $shouldDetect = false, RiskLevel $riskLevel = RiskLevel::LOW)
    {
        $this->shouldDetect = $shouldDetect;
        $this->riskLevel = $riskLevel;
        parent::__construct('test-detector', true);
    }

    protected function performDetection(Context $context): DetectionResult
    {
        if ($this->shouldDetect) {
            $assessment = new RiskAssessment($this->riskLevel->getScore(), $this->riskLevel, ['detection' => 'Test detection triggered']);

            return new DetectionResult($assessment, ['test_triggered' => true]);
        }

        $assessment = new RiskAssessment(RiskLevel::LOW->getScore(), RiskLevel::LOW, ['detection' => 'No risk detected']);

        return new DetectionResult($assessment, ['test_triggered' => false]);
    }
}
