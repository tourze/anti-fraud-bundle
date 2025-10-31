<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\RiskAssessment;
use Tourze\AntiFraudBundle\Model\UserBehavior;
use Tourze\AntiFraudBundle\Rule\Action\ActionInterface;
use Tourze\AntiFraudBundle\Service\ActionExecutor;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ActionExecutor::class)]
#[RunTestsInSeparateProcesses]
final class ActionExecutorTest extends AbstractIntegrationTestCase
{
    private ActionExecutor $executor;

    protected function onSetUp(): void
    {
        $this->executor = self::getService(ActionExecutor::class);
    }

    public function testBasicFunctionality(): void
    {
        $this->assertInstanceOf(ActionExecutor::class, $this->executor);
        $this->assertEmpty($this->executor->getExecutedActions());
    }

    public function testClearExecutedActions(): void
    {
        $this->executor->clearExecutedActions();
        $this->assertEmpty($this->executor->getExecutedActions());
    }

    public function testExecute(): void
    {
        // 创建一个测试动作
        $action = new class implements ActionInterface {
            public function getName(): string
            {
                return 'test_action';
            }

            public function execute(Request $request): Response
            {
                return new Response('test response', 200);
            }
        };

        $request = new Request();
        $behavior = new UserBehavior(
            userId: 'user123',
            sessionId: 'session456',
            ip: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
            action: 'test'
        );
        $context = new Context($behavior);
        $assessment = new RiskAssessment(0.1, RiskLevel::LOW);

        $response = $this->executor->execute($action, $request, $context, $assessment);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(1, $this->executor->getExecutedActions());
    }

    public function testExecuteMultiple(): void
    {
        // 创建多个测试动作
        $action1 = new class implements ActionInterface {
            public function getName(): string
            {
                return 'test_action_1';
            }

            public function execute(Request $request): Response
            {
                return new Response('test response 1', 200);
            }
        };

        $action2 = new class implements ActionInterface {
            public function getName(): string
            {
                return 'test_action_2';
            }

            public function execute(Request $request): Response
            {
                return new Response('test response 2', 200);
            }
        };

        $request = new Request();
        $behavior = new UserBehavior(
            userId: 'user123',
            sessionId: 'session456',
            ip: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
            action: 'test'
        );
        $context = new Context($behavior);
        $assessment = new RiskAssessment(0.1, RiskLevel::LOW);

        $response = $this->executor->executeMultiple([$action1, $action2], $request, $context, $assessment);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertCount(2, $this->executor->getExecutedActions());
    }

    public function testExecuteMultipleWithBlockingResponse(): void
    {
        // 创建一个阻断性动作
        $blockingAction = new class implements ActionInterface {
            public function getName(): string
            {
                return 'blocking_action';
            }

            public function execute(Request $request): Response
            {
                return new Response('blocked', 403);
            }
        };

        // 创建一个正常动作
        $normalAction = new class implements ActionInterface {
            public function getName(): string
            {
                return 'normal_action';
            }

            public function execute(Request $request): Response
            {
                return new Response('normal', 200);
            }
        };

        $request = new Request();
        $behavior = new UserBehavior(
            userId: 'user123',
            sessionId: 'session456',
            ip: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
            action: 'test'
        );
        $context = new Context($behavior);
        $assessment = new RiskAssessment(0.8, RiskLevel::HIGH);

        $response = $this->executor->executeMultiple([$blockingAction, $normalAction], $request, $context, $assessment);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('blocked', $response->getContent());
        // 只有阻断性动作被执行
        $this->assertCount(1, $this->executor->getExecutedActions());
    }

    public function testGetActionStatistics(): void
    {
        // 清空之前的执行记录
        $this->executor->clearExecutedActions();

        // 创建测试动作
        $action1 = new class implements ActionInterface {
            public function getName(): string
            {
                return 'action_1';
            }

            public function execute(Request $request): Response
            {
                return new Response('test', 200);
            }
        };

        $action2 = new class implements ActionInterface {
            public function getName(): string
            {
                return 'action_2';
            }

            public function execute(Request $request): Response
            {
                return new Response('test', 200);
            }
        };

        $request = new Request();
        $behavior = new UserBehavior(
            userId: 'user123',
            sessionId: 'session456',
            ip: '192.168.1.1',
            userAgent: 'Mozilla/5.0',
            action: 'test'
        );
        $context = new Context($behavior);
        $assessment = new RiskAssessment(0.1, RiskLevel::LOW);

        // 执行动作
        $this->executor->execute($action1, $request, $context, $assessment);
        $this->executor->execute($action1, $request, $context, $assessment);
        $this->executor->execute($action2, $request, $context, $assessment);

        $stats = $this->executor->getActionStatistics();

        $this->assertArrayHasKey('action_1', $stats);
        $this->assertArrayHasKey('action_2', $stats);
        $this->assertEquals(2, $stats['action_1']['count']);
        $this->assertEquals(1, $stats['action_2']['count']);
        $this->assertGreaterThan(0, $stats['action_1']['total_duration']);
        $this->assertGreaterThan(0, $stats['action_2']['total_duration']);
    }
}
