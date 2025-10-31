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
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\AntiFraudBundle\Entity\DetectionLog;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;

#[AdminCrud(
    routePath: '/anti-fraud/detection-log',
    routeName: 'anti_fraud_detection_log',
)]
final class DetectionLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DetectionLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('检测日志')
            ->setEntityLabelInPlural('检测日志')
            ->setPageTitle('index', '检测日志列表')
            ->setPageTitle('detail', '检测日志详情')
            ->setPageTitle('edit', '编辑检测日志')
            ->setPageTitle('new', '新建检测日志')
            ->setDefaultSort(['createdTime' => 'DESC'])
            ->setPaginatorPageSize(50)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')->onlyOnIndex();

        yield TextField::new('userId', '用户ID')
            ->setMaxLength(255)
            ->setHelp('触发检测的用户ID')
        ;

        yield TextField::new('sessionId', '会话ID')
            ->setMaxLength(255)
            ->setHelp('用户会话标识符')
        ;

        yield TextField::new('ipAddress', 'IP地址')
            ->setMaxLength(45)
            ->setHelp('用户访问IP地址')
        ;

        yield TextField::new('userAgent', '用户代理')
            ->onlyOnDetail()
            ->setHelp('浏览器用户代理信息')
        ;

        yield TextField::new('action', '操作行为')
            ->setMaxLength(255)
            ->setHelp('用户执行的操作')
        ;

        $riskLevelField = EnumField::new('riskLevel', '风险等级');
        $riskLevelField->setEnumCases(RiskLevel::cases());
        yield $riskLevelField->setHelp('检测到的风险等级');

        yield NumberField::new('riskScore', '风险分数')
            ->setNumDecimals(2)
            ->setHelp('风险评分（0-1）')
        ;

        yield ArrayField::new('matchedRules', '匹配规则')
            ->onlyOnDetail()
            ->setHelp('触发的规则列表')
        ;

        yield ArrayField::new('detectionDetails', '检测详情')
            ->onlyOnDetail()
            ->setHelp('详细检测信息')
        ;

        yield TextField::new('actionTaken', '采取行动')
            ->setMaxLength(255)
            ->setHelp('系统采取的响应行动')
        ;

        yield ArrayField::new('actionDetails', '行动详情')
            ->onlyOnDetail()
            ->setHelp('行动执行的详细信息')
        ;

        yield TextField::new('requestPath', '请求路径')
            ->onlyOnDetail()
            ->setHelp('HTTP请求路径')
        ;

        yield TextField::new('requestMethod', '请求方法')
            ->onlyOnDetail()
            ->setHelp('HTTP请求方法')
        ;

        yield ArrayField::new('requestHeaders', '请求头')
            ->onlyOnDetail()
            ->setHelp('HTTP请求头信息')
        ;

        yield TextField::new('countryCode', '国家代码')
            ->setMaxLength(2)
            ->setHelp('IP所属国家代码')
        ;

        yield BooleanField::new('isProxy', '代理IP')
            ->setHelp('是否通过代理访问')
        ;

        yield BooleanField::new('isBot', '机器人')
            ->setHelp('是否为机器人流量')
        ;

        yield IntegerField::new('responseTime', '响应时间')
            ->setHelp('请求处理时间(毫秒)')
        ;

        yield DateTimeField::new('createdTime', '创建时间')
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->onlyOnIndex()
            ->setHelp('日志记录时间')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('userId', '用户ID'))
            ->add(TextFilter::new('sessionId', '会话ID'))
            ->add(TextFilter::new('ipAddress', 'IP地址'))
            ->add(TextFilter::new('action', '操作行为'))
            ->add('riskLevel')
            ->add(TextFilter::new('actionTaken', '采取行动'))
            ->add(TextFilter::new('countryCode', '国家代码'))
            ->add(BooleanFilter::new('isProxy', '代理IP'))
            ->add(BooleanFilter::new('isBot', '机器人'))
            ->add(DateTimeFilter::new('createdTime', '创建时间'))
        ;
    }
}
