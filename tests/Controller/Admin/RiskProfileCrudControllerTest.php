<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AntiFraudBundle\Controller\Admin\RiskProfileCrudController;
use Tourze\AntiFraudBundle\Entity\RiskProfile;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * 风险档案管理控制器测试
 * @internal
 */
#[CoversClass(RiskProfileCrudController::class)]
#[RunTestsInSeparateProcesses]
final class RiskProfileCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): RiskProfileCrudController
    {
        return self::getService(RiskProfileCrudController::class);
    }

    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '标识类型' => ['标识类型'];
        yield '标识值' => ['标识值'];
        yield '风险等级' => ['风险等级'];
        yield '风险分数' => ['风险分数'];
        yield '总检测次数' => ['总检测次数'];
        yield '高风险检测' => ['高风险检测'];
        yield '中风险检测' => ['中风险检测'];
        yield '低风险检测' => ['低风险检测'];
        yield '阻止操作' => ['阻止操作'];
        yield '限流操作' => ['限流操作'];
        yield '最后高风险时间' => ['最后高风险时间'];
        yield '最后检测时间' => ['最后检测时间'];
        yield '白名单' => ['白名单'];
        yield '黑名单' => ['黑名单'];
        yield '备注' => ['备注'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    public static function provideNewPageFields(): iterable
    {
        yield 'identifierType' => ['identifierType'];
        yield 'identifierValue' => ['identifierValue'];
        yield 'riskLevel' => ['riskLevel'];
        yield 'riskScore' => ['riskScore'];
        yield 'isWhitelisted' => ['isWhitelisted'];
        yield 'isBlacklisted' => ['isBlacklisted'];
        yield 'notes' => ['notes'];
    }

    public static function provideEditPageFields(): iterable
    {
        yield 'identifierType' => ['identifierType'];
        yield 'identifierValue' => ['identifierValue'];
        yield 'riskLevel' => ['riskLevel'];
        yield 'riskScore' => ['riskScore'];
        yield 'isWhitelisted' => ['isWhitelisted'];
        yield 'isBlacklisted' => ['isBlacklisted'];
        yield 'notes' => ['notes'];
    }

    public function testGetEntityFqcn(): void
    {
        self::assertSame(RiskProfile::class, RiskProfileCrudController::getEntityFqcn());
    }

    public function testIndexPageAccessibleForAuthenticatedAdmin(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());

        $client->request('GET', '/admin/anti-fraud/risk-profile');

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
        $content = $response->getContent();
        $this->assertIsString($content);
        $this->assertStringContainsString('风险档案列表', $content);
    }

    public function testConfigureFields(): void
    {
        $controller = new RiskProfileCrudController();
        $fields = iterator_to_array($controller->configureFields('index'));

        self::assertIsArray($fields);
        self::assertNotEmpty($fields);

        // 验证包含的主要字段
        $fieldNames = [];
        foreach ($fields as $field) {
            if ($field instanceof FieldInterface) {
                $fieldNames[] = $field->getAsDto()->getProperty();
            } elseif (is_string($field)) {
                $fieldNames[] = $field;
            }
        }

        self::assertContains('id', $fieldNames);
        self::assertContains('identifierType', $fieldNames);
        self::assertContains('identifierValue', $fieldNames);
        self::assertContains('riskLevel', $fieldNames);
        self::assertContains('riskScore', $fieldNames);
        self::assertContains('totalDetections', $fieldNames);
        self::assertContains('highRiskDetections', $fieldNames);
        self::assertContains('isWhitelisted', $fieldNames);
        self::assertContains('isBlacklisted', $fieldNames);
    }

    public function testConfigureCrud(): void
    {
        $controller = new RiskProfileCrudController();
        $crudStub = Crud::new();
        $crud = $controller->configureCrud($crudStub);

        // 验证配置方法正确运行
        self::assertSame($crudStub, $crud);
    }

    public function testConfigureFilters(): void
    {
        $controller = new RiskProfileCrudController();
        $filtersStub = Filters::new();
        $filters = $controller->configureFilters($filtersStub);

        // 验证配置方法正确运行
        self::assertSame($filtersStub, $filters);
    }

    public function testSearchFunctionalityWithValidQuery(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());

        $client->request('GET', '/admin/anti-fraud/risk-profile?query=test');

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
    }

    public function testFilterFunctionalityWithIdentifierType(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());

        $client->request('GET', '/admin/anti-fraud/risk-profile?filters[identifierType]=user');

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
    }

    public function testFilterFunctionalityWithRiskLevel(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());

        $client->request('GET', '/admin/anti-fraud/risk-profile?filters[riskLevel]=high');

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
    }

    public function testFilterFunctionalityWithWhitelist(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());

        $client->request('GET', '/admin/anti-fraud/risk-profile?filters[isWhitelisted]=true');

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
    }

    public function testFilterFunctionalityWithBlacklist(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());

        $client->request('GET', '/admin/anti-fraud/risk-profile?filters[isBlacklisted]=true');

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
    }

    public function testFilterFunctionalityWithIdentifierValue(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());

        $client->request('GET', '/admin/anti-fraud/risk-profile?filters[identifierValue]=test-value');

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
    }

    public function testFilterFunctionalityWithDateRange(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());

        $client->request('GET', '/admin/anti-fraud/risk-profile?filters[lastHighRiskTime][after]=2024-01-01');

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
    }

    public function testFilterFunctionalityWithLastDetectionTime(): void
    {
        $client = self::createClientWithDatabase();
        $client->loginUser($this->createAdminUser());

        $client->request('GET', '/admin/anti-fraud/risk-profile?filters[lastDetectionTime][before]=2024-12-31');

        $response = $client->getResponse();
        $this->assertTrue($response->isSuccessful());
    }
}
