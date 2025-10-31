<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Exception;

/**
 * 无效的标识符类型异常
 */
class InvalidIdentifierTypeException extends \RuntimeException
{
    public function __construct(string $identifierType, ?\Throwable $previous = null)
    {
        $message = sprintf('Invalid identifier type: %s. Valid types are: user, ip, session', $identifierType);
        parent::__construct($message, 0, $previous);
    }
}
