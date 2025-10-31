<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Detector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tourze\AntiFraudBundle\Contract\DetectorInterface;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\DetectionResult;

/**
 * @internal
 */
#[CoversClass(DetectorInterface::class)]
final class DetectorInterfaceTest extends TestCase
{
    protected function setUp(): void
    {
        // This test only tests interface structure, no setup needed
    }

    public function testDetectorInterfaceExists(): void
    {
        $this->assertTrue(interface_exists(DetectorInterface::class));
    }

    public function testDetectorInterfaceHasDetectMethod(): void
    {
        $reflection = new \ReflectionClass(DetectorInterface::class);

        $this->assertTrue($reflection->hasMethod('detect'));

        $detectMethod = $reflection->getMethod('detect');
        $this->assertTrue($detectMethod->isPublic());

        $parameters = $detectMethod->getParameters();
        $this->assertCount(1, $parameters);
        $this->assertSame('context', $parameters[0]->getName());
        $this->assertSame(Context::class, (string) $parameters[0]->getType());

        $returnType = $detectMethod->getReturnType();
        $this->assertSame(DetectionResult::class, (string) $returnType);
    }

    public function testDetectorInterfaceHasGetNameMethod(): void
    {
        $reflection = new \ReflectionClass(DetectorInterface::class);

        $this->assertTrue($reflection->hasMethod('getName'));

        $getNameMethod = $reflection->getMethod('getName');
        $this->assertTrue($getNameMethod->isPublic());

        $returnType = $getNameMethod->getReturnType();
        $this->assertSame('string', (string) $returnType);
    }

    public function testDetectorInterfaceHasIsEnabledMethod(): void
    {
        $reflection = new \ReflectionClass(DetectorInterface::class);

        $this->assertTrue($reflection->hasMethod('isEnabled'));

        $isEnabledMethod = $reflection->getMethod('isEnabled');
        $this->assertTrue($isEnabledMethod->isPublic());

        $returnType = $isEnabledMethod->getReturnType();
        $this->assertSame('bool', (string) $returnType);
    }
}
