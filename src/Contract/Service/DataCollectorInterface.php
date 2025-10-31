<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Contract\Service;

use Symfony\Component\HttpFoundation\Request;
use Tourze\AntiFraudBundle\Model\Context;

interface DataCollectorInterface
{
    public function collect(Request $request): Context;
}
