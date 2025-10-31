<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\AntiFraudBundle\DependencyInjection\AntiFraudExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(AntiFraudExtension::class)]
final class AntiFraudExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private AntiFraudExtension $extension;

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        parent::setUp();
        $this->extension = new AntiFraudExtension();
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'test');
    }

    public function testLoadLoadServicesConfiguration(): void
    {
        $this->extension->load([], $this->container);

        // 验证服务配置加载成功 - AutoExtension会自动扫描和注册服务
        $this->assertGreaterThan(0, count($this->container->getDefinitions()));
    }
}
