<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class AntiFraudExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
