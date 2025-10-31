<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Double;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\AntiFraudBundle\Rule\Action\ActionInterface;

/**
 * ActionInterface 的测试双对象
 *
 * @internal
 */
final class TestAction implements ActionInterface
{
    public function __construct(
        private readonly string $name,
        private readonly ?Response $response = null,
        private readonly mixed $executeCallback = null,
    ) {
    }

    public function execute(Request $request): ?Response
    {
        if (null !== $this->executeCallback && is_callable($this->executeCallback)) {
            $result = ($this->executeCallback)($request);
            assert($result instanceof Response || null === $result);

            return $result;
        }

        return $this->response;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
