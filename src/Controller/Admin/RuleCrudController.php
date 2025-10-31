<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\AntiFraudBundle\Entity\Rule;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;

#[AdminCrud(
    routePath: '/anti-fraud/rule',
    routeName: 'anti_fraud_rule',
)]
final class RuleCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Rule::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('反欺诈规则')
            ->setEntityLabelInPlural('反欺诈规则')
            ->setPageTitle('index', '反欺诈规则列表')
            ->setPageTitle('detail', '规则详情')
            ->setPageTitle('edit', '编辑规则')
            ->setPageTitle('new', '新建规则')
            ->setDefaultSort(['priority' => 'DESC', 'enabled' => 'DESC'])
            ->setPaginatorPageSize(30)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnIndex();

        yield TextField::new('name', '规则名称')
            ->setMaxLength(255)
            ->setRequired(true)
            ->setHelp('规则的唯一名称')
        ;

        yield TextareaField::new('condition', '规则条件')
            ->setRequired(true)
            ->setMaxLength(65535)
            ->setHelp('规则匹配条件表达式')
            ->hideOnIndex()
        ;

        $riskLevelField = EnumField::new('riskLevel', '风险等级');
        $riskLevelField->setEnumCases(RiskLevel::cases());
        yield $riskLevelField
            ->setRequired(true)
            ->setHelp('触发规则时的风险等级')
        ;

        yield ArrayField::new('actions', '执行动作')
            ->setRequired(false)
            ->setHelp('规则触发时执行的动作')
            ->hideOnIndex()
        ;

        yield IntegerField::new('priority', '优先级')
            ->setRequired(true)
            ->setHelp('规则执行优先级（数值越大优先级越高）')
        ;

        yield BooleanField::new('terminal', '终止后续规则')
            ->setHelp('是否在此规则匹配后终止后续规则的执行')
        ;

        yield BooleanField::new('enabled', '启用状态')
            ->setHelp('规则是否启用')
        ;

        yield TextareaField::new('description', '规则描述')
            ->setMaxLength(65535)
            ->setHelp('规则的详细描述')
            ->hideOnIndex()
        ;

        yield DateTimeField::new('createdTime', '创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
            ->setHelp('规则创建时间')
        ;

        yield DateTimeField::new('updatedTime', '更新时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->hideOnForm()
            ->setHelp('规则最后更新时间')
        ;

        yield IntegerField::new('version', '版本号')
            ->onlyOnDetail()
            ->setHelp('乐观锁版本号')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('name', '规则名称'))
            ->add('riskLevel')
            ->add(NumericFilter::new('priority', '优先级'))
            ->add(BooleanFilter::new('terminal', '终止后续规则'))
            ->add(BooleanFilter::new('enabled', '启用状态'))
            ->add(DateTimeFilter::new('createdTime', '创建时间'))
            ->add(DateTimeFilter::new('updatedTime', '更新时间'))
        ;
    }
}
