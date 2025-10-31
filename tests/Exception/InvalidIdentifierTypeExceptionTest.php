<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AntiFraudBundle\Exception\InvalidIdentifierTypeException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(InvalidIdentifierTypeException::class)]
#[RunTestsInSeparateProcesses]
final class InvalidIdentifierTypeExceptionTest extends AbstractExceptionTestCase
{
    public function testConstructWithInvalidType(): void
    {
        $invalidType = 'invalid_type';
        $exception = new InvalidIdentifierTypeException($invalidType);

        $expectedMessage = 'Invalid identifier type: invalid_type. Valid types are: user, ip, session';
        $this->assertSame($expectedMessage, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructWithPreviousException(): void
    {
        $previousException = new \RuntimeException('Previous error');
        $exception = new InvalidIdentifierTypeException('bad_type', $previousException);

        $expectedMessage = 'Invalid identifier type: bad_type. Valid types are: user, ip, session';
        $this->assertSame($expectedMessage, $exception->getMessage());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testExtendsRuntimeException(): void
    {
        $exception = new InvalidIdentifierTypeException('test');
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}
