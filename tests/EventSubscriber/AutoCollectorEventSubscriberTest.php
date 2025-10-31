<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\EventSubscriber;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Tourze\AntiFraudBundle\Contract\Service\DataCollectorInterface as DataCollector;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\EventSubscriber\AutoCollectorEventSubscriber;
use Tourze\AntiFraudBundle\Model\Context;
use Tourze\AntiFraudBundle\Model\DetectionResult;
use Tourze\AntiFraudBundle\Model\UserBehavior;
use Tourze\AntiFraudBundle\Tests\Double\TestAction;
use Tourze\AntiFraudBundle\Tests\Double\TestDataCollector;
use Tourze\AntiFraudBundle\Tests\Double\TestDynamicRuleEngine;
use Tourze\PHPUnitSymfonyKernelTest\AbstractEventSubscriberTestCase;

/**
 * @internal
 */
#[CoversClass(AutoCollectorEventSubscriber::class)]
#[RunTestsInSeparateProcesses]
final class AutoCollectorEventSubscriberTest extends AbstractEventSubscriberTestCase
{
    private TestDataCollector $dataCollector;

    private TestDynamicRuleEngine $ruleEngine;

    private AutoCollectorEventSubscriber $subscriber;

    protected function onSetUp(): void
    {
        $this->dataCollector = new TestDataCollector();
        $this->ruleEngine = new TestDynamicRuleEngine();

        // 替换容器中的服务为测试双对象，确保测试的可控性
        self::getContainer()->set(DataCollector::class, $this->dataCollector);
        self::getContainer()->set('Tourze\AntiFraudBundle\Rule\Engine\DynamicRuleEngine', $this->ruleEngine);

        $this->subscriber = self::getService(AutoCollectorEventSubscriber::class);
    }

    public function testGetSubscribedEvents(): void
    {
        $events = AutoCollectorEventSubscriber::getSubscribedEvents();

        $this->assertArrayHasKey(KernelEvents::REQUEST, $events);
        $this->assertArrayHasKey(KernelEvents::RESPONSE, $events);

        // Check priorities
        $this->assertEquals(['onKernelRequest', 255], $events[KernelEvents::REQUEST]);
        $this->assertEquals(['onKernelResponse', -255], $events[KernelEvents::RESPONSE]);
    }

    public function testOnKernelRequestWithMainRequest(): void
    {
        $request = Request::create('/login', 'POST');
        $kernel = new class implements HttpKernelInterface {
            public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
            {
                return new Response();
            }
        };
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // 创建测试上下文和检测结果
        $userBehavior = new UserBehavior('user123', 'session123', '192.168.1.1', 'Mozilla/5.0', 'login');
        $context = new Context($userBehavior);

        $action = new TestAction('log');
        $detectionResult = new DetectionResult(RiskLevel::LOW, $action, []);

        // 设置测试双对象的返回值
        $this->dataCollector->setReturnContext($context);
        $this->ruleEngine->setReturnResult($detectionResult);

        $this->subscriber->onKernelRequest($event);

        // Verify the result was stored in request attributes
        $this->assertSame($detectionResult, $request->attributes->get('_antifraud_result'));
    }

    public function testOnKernelRequestWithSubRequest(): void
    {
        $request = Request::create('/login', 'POST');
        $kernel = new class implements HttpKernelInterface {
            public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
            {
                return new Response();
            }
        };
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::SUB_REQUEST);

        // 不应该调用任何服务
        $this->subscriber->onKernelRequest($event);

        $this->assertNull($request->attributes->get('_antifraud_result'));
    }

    public function testOnKernelRequestWithHighRiskAction(): void
    {
        $request = Request::create('/login', 'POST');
        $kernel = new class implements HttpKernelInterface {
            public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
            {
                return new Response();
            }
        };
        $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

        // 创建测试上下文和检测结果
        $userBehavior = new UserBehavior('user123', 'session123', '192.168.1.1', 'Mozilla/5.0', 'login');
        $context = new Context($userBehavior);

        $blockResponse = new Response('Access denied', 403);
        $action = new TestAction('block', $blockResponse);
        $detectionResult = new DetectionResult(RiskLevel::HIGH, $action, []);

        // 设置测试双对象的返回值
        $this->dataCollector->setReturnContext($context);
        $this->ruleEngine->setReturnResult($detectionResult);

        $this->subscriber->onKernelRequest($event);

        // Verify the response was set
        $this->assertSame($blockResponse, $event->getResponse());
    }

    public function testOnKernelRequestSkipsStaticResources(): void
    {
        $staticPaths = [
            '/style.css',
            '/script.js',
            '/image.png',
            '/icon.ico',
            '/font.woff2',
        ];

        foreach ($staticPaths as $path) {
            $request = Request::create($path);
            $kernel = new class implements HttpKernelInterface {
                public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
                {
                    return new Response();
                }
            };
            $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

            // 不应该调用任何服务
            $this->subscriber->onKernelRequest($event);

            // 验证没有设置反欺诈结果
            $this->assertNull($request->attributes->get('_antifraud_result'));
        }
    }

    public function testOnKernelRequestSkipsAdminPaths(): void
    {
        $adminPaths = [
            '/admin/antifraud',
            '/admin/antifraud/rules',
            '/admin/antifraud/logs',
        ];

        foreach ($adminPaths as $path) {
            $request = Request::create($path);
            $kernel = new class implements HttpKernelInterface {
                public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
                {
                    return new Response();
                }
            };
            $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

            // 不应该调用任何服务
            $this->subscriber->onKernelRequest($event);

            // 验证没有设置反欺诈结果
            $this->assertNull($request->attributes->get('_antifraud_result'));
        }
    }

    public function testOnKernelRequestSkipsHealthChecks(): void
    {
        $healthPaths = ['/health', '/ping'];

        foreach ($healthPaths as $path) {
            $request = Request::create($path);
            $kernel = new class implements HttpKernelInterface {
                public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
                {
                    return new Response();
                }
            };
            $event = new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);

            // 不应该调用任何服务
            $this->subscriber->onKernelRequest($event);

            // 验证没有设置反欺诈结果
            $this->assertNull($request->attributes->get('_antifraud_result'));
        }
    }

    public function testOnKernelResponse(): void
    {
        $request = Request::create('/login');
        $response = new Response();
        $kernel = new class implements HttpKernelInterface {
            public function handle(Request $request, int $type = self::MAIN_REQUEST, bool $catch = true): Response
            {
                return new Response();
            }
        };
        $event = new ResponseEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST, $response);

        // For now, response handler does nothing, but this tests the method exists
        $this->subscriber->onKernelResponse($event);

        // Verify the response event was handled without throwing exceptions
        $this->assertSame($response, $event->getResponse());
    }
}
