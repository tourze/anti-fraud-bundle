<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Double;

use Tourze\AntiFraudBundle\Contract\DetectorInterface;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\DetectionResult;
use Tourze\AntiFraudBundle\Rule\Action\ActionInterface;

/**
 * DetectorInterface 的测试双对象
 *
 * @internal
 */
final class TestDetector implements DetectorInterface
{
    public function __construct(
        private readonly string $name,
        private readonly RiskLevel $riskLevel,
        private readonly ActionInterface $action,
        private readonly bool $enabled = true,
        private readonly mixed $detectCallback = null,
    ) {
    }

    public function detect(Context $context): DetectionResult
    {
        if (null !== $this->detectCallback && is_callable($this->detectCallback)) {
            $result = ($this->detectCallback)($context);
            assert($result instanceof DetectionResult);

            return $result;
        }

        return new DetectionResult($this->riskLevel, $this->action, [], ['message' => 'Test detection']);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
