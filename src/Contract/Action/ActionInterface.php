<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Contract\Action;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface ActionInterface
{
    public function execute(Request $request): ?Response;

    public function getName(): string;
}
