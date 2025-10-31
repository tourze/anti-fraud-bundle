<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\AntiFraudBundle\Entity\RiskProfile;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;

#[AdminCrud(
    routePath: '/anti-fraud/risk-profile',
    routeName: 'anti_fraud_risk_profile',
)]
final class RiskProfileCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return RiskProfile::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('风险档案')
            ->setEntityLabelInPlural('风险档案')
            ->setPageTitle('index', '风险档案列表')
            ->setPageTitle('detail', '风险档案详情')
            ->setPageTitle('edit', '编辑风险档案')
            ->setPageTitle('new', '新建风险档案')
            ->setDefaultSort(['riskScore' => 'DESC', 'updatedTime' => 'DESC'])
            ->setPaginatorPageSize(50)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnIndex();

        yield ChoiceField::new('identifierType', '标识类型')
            ->setChoices([
                '用户' => RiskProfile::TYPE_USER,
                'IP地址' => RiskProfile::TYPE_IP,
                '设备' => RiskProfile::TYPE_DEVICE,
                '会话' => RiskProfile::TYPE_SESSION,
            ])
            ->setHelp('风险档案的标识类型')
        ;

        yield TextField::new('identifierValue', '标识值')
            ->setMaxLength(255)
            ->setHelp('具体的标识值')
        ;

        $riskLevelField = EnumField::new('riskLevel', '风险等级');
        $riskLevelField->setEnumCases(RiskLevel::cases());
        yield $riskLevelField->setHelp('当前风险等级');

        yield NumberField::new('riskScore', '风险分数')
            ->setNumDecimals(2)
            ->setHelp('风险评分（0-1）')
        ;

        yield IntegerField::new('totalDetections', '总检测次数')
            ->setHelp('历史检测总数')
        ;

        yield IntegerField::new('highRiskDetections', '高风险检测')
            ->setHelp('高风险检测次数')
        ;

        yield IntegerField::new('mediumRiskDetections', '中风险检测')
            ->setHelp('中等风险检测次数')
        ;

        yield IntegerField::new('lowRiskDetections', '低风险检测')
            ->setHelp('低风险检测次数')
        ;

        yield IntegerField::new('blockedActions', '阻止操作')
            ->setHelp('被阻止的操作次数')
        ;

        yield IntegerField::new('throttledActions', '限流操作')
            ->setHelp('被限流的操作次数')
        ;

        yield ArrayField::new('riskFactors', '风险因素')
            ->onlyOnDetail()
            ->setHelp('详细风险因素信息')
        ;

        yield ArrayField::new('behaviorPatterns', '行为模式')
            ->onlyOnDetail()
            ->setHelp('用户行为模式分析')
        ;

        yield DateTimeField::new('lastHighRiskTime', '最后高风险时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
            ->setHelp('最后一次高风险检测的时间')
        ;

        yield DateTimeField::new('lastDetectionTime', '最后检测时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
            ->setHelp('最后一次检测的时间')
        ;

        yield BooleanField::new('isWhitelisted', '白名单')
            ->setHelp('是否在白名单中')
        ;

        yield BooleanField::new('isBlacklisted', '黑名单')
            ->setHelp('是否在黑名单中')
        ;

        yield TextareaField::new('notes', '备注')
            ->setMaxLength(255)
            ->setHelp('管理员备注信息')
        ;

        yield ArrayField::new('metadata', '元数据')
            ->onlyOnDetail()
            ->setHelp('其他元数据信息')
        ;

        yield DateTimeField::new('createdTime', '创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
            ->setHelp('档案创建时间')
        ;

        yield DateTimeField::new('updatedTime', '更新时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
            ->setHelp('档案最后更新时间')
        ;

        yield IntegerField::new('version', '版本号')
            ->onlyOnDetail()
            ->setHelp('乐观锁版本号')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('identifierType', '标识类型')
                ->setChoices([
                    '用户' => RiskProfile::TYPE_USER,
                    'IP地址' => RiskProfile::TYPE_IP,
                    '设备' => RiskProfile::TYPE_DEVICE,
                    '会话' => RiskProfile::TYPE_SESSION,
                ]))
            ->add(TextFilter::new('identifierValue', '标识值'))
            ->add('riskLevel')
            ->add(BooleanFilter::new('isWhitelisted', '白名单'))
            ->add(BooleanFilter::new('isBlacklisted', '黑名单'))
            ->add(DateTimeFilter::new('lastHighRiskTime', '最后高风险时间'))
            ->add(DateTimeFilter::new('lastDetectionTime', '最后检测时间'))
            ->add(DateTimeFilter::new('createdTime', '创建时间'))
            ->add(DateTimeFilter::new('updatedTime', '更新时间'))
        ;
    }
}
