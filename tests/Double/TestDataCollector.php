<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Double;

use Symfony\Component\HttpFoundation\Request;
use Tourze\AntiFraudBundle\Contract\Service\DataCollectorInterface as DataCollector;
use Tourze\AntiFraudBundle\Model\Context;

/**
 * DataCollector 的测试双对象
 *
 * @internal
 */
final class TestDataCollector implements DataCollector
{
    private ?Context $returnContext = null;

    public function collect(Request $request): Context
    {
        return $this->returnContext ?? throw new \RuntimeException('No context set');
    }

    public function setReturnContext(?Context $context): void
    {
        $this->returnContext = $context;
    }
}
