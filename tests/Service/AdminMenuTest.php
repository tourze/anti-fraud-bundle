<?php

namespace Tourze\AntiFraudBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use Knp\Menu\MenuFactory;
use Knp\Menu\MenuItem;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AntiFraudBundle\Controller\Admin\DetectionLogCrudController;
use Tourze\AntiFraudBundle\Controller\Admin\RiskProfileCrudController;
use Tourze\AntiFraudBundle\Controller\Admin\RuleCrudController;
use Tourze\AntiFraudBundle\Service\AdminMenu;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private AdminMenu $adminMenu;

    private LinkGeneratorInterface $linkGenerator;

    public function testInvokeAddsAntiFraudMenu(): void
    {
        $this->assertInstanceOf(AdminMenu::class, $this->adminMenu);

        // 创建真实的菜单项
        $factory = new MenuFactory();
        $mainItem = new MenuItem('root', $factory);

        // 执行菜单构建
        $this->adminMenu->__invoke($mainItem);

        // 验证反欺诈管理菜单被创建
        $antiFraudMenu = $mainItem->getChild('反欺诈管理');
        $this->assertInstanceOf(ItemInterface::class, $antiFraudMenu);

        // 验证反欺诈规则子菜单
        $ruleMenuItem = $antiFraudMenu->getChild('反欺诈规则');
        $this->assertInstanceOf(ItemInterface::class, $ruleMenuItem);
        $this->assertSame('/admin/antifraud/rule', $ruleMenuItem->getUri());
        $this->assertSame('fas fa-shield-alt', $ruleMenuItem->getAttribute('icon'));

        // 验证风险档案子菜单
        $riskProfileMenuItem = $antiFraudMenu->getChild('风险档案');
        $this->assertInstanceOf(ItemInterface::class, $riskProfileMenuItem);
        $this->assertSame('/admin/antifraud/risk-profile', $riskProfileMenuItem->getUri());
        $this->assertSame('fas fa-user-shield', $riskProfileMenuItem->getAttribute('icon'));

        // 验证检测日志子菜单
        $detectionLogMenuItem = $antiFraudMenu->getChild('检测日志');
        $this->assertInstanceOf(ItemInterface::class, $detectionLogMenuItem);
        $this->assertSame('/admin/antifraud/detection-log', $detectionLogMenuItem->getUri());
        $this->assertSame('fas fa-history', $detectionLogMenuItem->getAttribute('icon'));
    }

    public function testInvokeWithExistingAntiFraudMenu(): void
    {
        $this->assertInstanceOf(AdminMenu::class, $this->adminMenu);

        // 创建已有反欺诈管理菜单的主菜单
        $factory = new MenuFactory();
        $mainItem = new MenuItem('root', $factory);
        $existingAntiFraudMenu = $mainItem->addChild('反欺诈管理');

        // 执行菜单构建
        $this->adminMenu->__invoke($mainItem);

        // 验证使用了现有的反欺诈管理菜单
        $antiFraudMenu = $mainItem->getChild('反欺诈管理');
        $this->assertSame($existingAntiFraudMenu, $antiFraudMenu);

        // 验证子菜单被正确添加
        $ruleMenuItem = $antiFraudMenu->getChild('反欺诈规则');
        $this->assertInstanceOf(ItemInterface::class, $ruleMenuItem);
        $this->assertSame('/admin/antifraud/rule', $ruleMenuItem->getUri());
        $this->assertSame('fas fa-shield-alt', $ruleMenuItem->getAttribute('icon'));

        $riskProfileMenuItem = $antiFraudMenu->getChild('风险档案');
        $this->assertInstanceOf(ItemInterface::class, $riskProfileMenuItem);
        $this->assertSame('/admin/antifraud/risk-profile', $riskProfileMenuItem->getUri());
        $this->assertSame('fas fa-user-shield', $riskProfileMenuItem->getAttribute('icon'));

        $detectionLogMenuItem = $antiFraudMenu->getChild('检测日志');
        $this->assertInstanceOf(ItemInterface::class, $detectionLogMenuItem);
        $this->assertSame('/admin/antifraud/detection-log', $detectionLogMenuItem->getUri());
        $this->assertSame('fas fa-history', $detectionLogMenuItem->getAttribute('icon'));
    }

    public function testMenuStructureIntegrity(): void
    {
        // 测试菜单结构的完整性
        $factory = new MenuFactory();
        $mainItem = new MenuItem('root', $factory);

        $this->adminMenu->__invoke($mainItem);

        $antiFraudMenu = $mainItem->getChild('反欺诈管理');
        $this->assertNotNull($antiFraudMenu);

        // 验证所有必要的子菜单都存在
        $expectedSubMenus = ['反欺诈规则', '风险档案', '检测日志'];
        foreach ($expectedSubMenus as $menuName) {
            $subMenu = $antiFraudMenu->getChild($menuName);
            $this->assertInstanceOf(ItemInterface::class, $subMenu, "子菜单 '{$menuName}' 应该存在");
            $this->assertNotEmpty($subMenu->getUri(), "子菜单 '{$menuName}' 应该有 URI");
            $this->assertNotEmpty($subMenu->getAttribute('icon'), "子菜单 '{$menuName}' 应该有图标");
        }
    }

    public function testMenuWithNullSubMenu(): void
    {
        // 测试当子菜单获取失败时的情况
        $factory = new MenuFactory();
        $mainItem = new MenuItem('root', $factory);

        // 模拟添加一个会返回 null 的子菜单
        $mockMainItem = new class($factory) extends MenuItem {
            public function __construct(MenuFactory $factory)
            {
                parent::__construct('root', $factory);
            }

            public function getChild(string $name): ?ItemInterface
            {
                if ('反欺诈管理' === $name) {
                    return null; // 模拟获取失败的情况
                }

                return parent::getChild($name);
            }

            public function addChild($child, array $options = []): ItemInterface
            {
                if (is_string($child) && '反欺诈管理' === $child) {
                    // 正常添加菜单项
                    return parent::addChild($child, $options);
                }

                return parent::addChild($child, $options);
            }
        };

        // 首次调用应该创建菜单
        $this->adminMenu->__invoke($mockMainItem);

        // 验证菜单被创建（这里主要测试不会抛出异常）
        $this->assertTrue(true, '菜单创建过程应该不抛出异常');
    }

    protected function onSetUp(): void
    {
        // 创建测试专用的LinkGenerator实现
        $this->linkGenerator = new class implements LinkGeneratorInterface {
            public function getCurdListPage(string $entityClass): string
            {
                return match ($entityClass) {
                    RuleCrudController::class => '/admin/antifraud/rule',
                    RiskProfileCrudController::class => '/admin/antifraud/risk-profile',
                    DetectionLogCrudController::class => '/admin/antifraud/detection-log',
                    default => '/admin/unknown',
                };
            }

            public function extractEntityFqcn(string $url): ?string
            {
                return match (true) {
                    str_contains($url, '/admin/antifraud/rule') => RuleCrudController::class,
                    str_contains($url, '/admin/antifraud/risk-profile') => RiskProfileCrudController::class,
                    str_contains($url, '/admin/antifraud/detection-log') => DetectionLogCrudController::class,
                    default => null,
                };
            }

            public function setDashboard(string $dashboardControllerFqcn): void
            {
                // 测试中不需要实现具体逻辑
            }
        };

        self::getContainer()->set(LinkGeneratorInterface::class, $this->linkGenerator);
        $this->adminMenu = self::getService(AdminMenu::class);
    }

    protected function getMenuProvider(): object
    {
        return $this->adminMenu;
    }
}
