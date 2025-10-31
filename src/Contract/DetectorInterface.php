<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Contract;

use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\DetectionResult;

interface DetectorInterface
{
    public function detect(Context $context): DetectionResult;

    public function getName(): string;

    public function isEnabled(): bool;
}
