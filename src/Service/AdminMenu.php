<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Service;

use Knp\Menu\ItemInterface;
use Tourze\AntiFraudBundle\Controller\Admin\DetectionLogCrudController;
use Tourze\AntiFraudBundle\Controller\Admin\RiskProfileCrudController;
use Tourze\AntiFraudBundle\Controller\Admin\RuleCrudController;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;

readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        if (null === $item->getChild('反欺诈管理')) {
            $item->addChild('反欺诈管理');
        }

        $antiFraudMenu = $item->getChild('反欺诈管理');
        if (null === $antiFraudMenu) {
            return;
        }

        // 反欺诈规则菜单
        $antiFraudMenu->addChild('反欺诈规则')
            ->setUri($this->linkGenerator->getCurdListPage(RuleCrudController::class))
            ->setAttribute('icon', 'fas fa-shield-alt')
        ;

        // 风险档案菜单
        $antiFraudMenu->addChild('风险档案')
            ->setUri($this->linkGenerator->getCurdListPage(RiskProfileCrudController::class))
            ->setAttribute('icon', 'fas fa-user-shield')
        ;

        // 检测日志菜单
        $antiFraudMenu->addChild('检测日志')
            ->setUri($this->linkGenerator->getCurdListPage(DetectionLogCrudController::class))
            ->setAttribute('icon', 'fas fa-history')
        ;
    }
}
