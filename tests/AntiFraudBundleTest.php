<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AntiFraudBundle\AntiFraudBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 * @phpstan-ignore symplify.forbiddenExtendOfNonAbstractClass
 */
#[CoversClass(AntiFraudBundle::class)]
#[RunTestsInSeparateProcesses]
final class AntiFraudBundleTest extends AbstractBundleTestCase
{
}
