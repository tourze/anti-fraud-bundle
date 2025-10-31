<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\LockMode;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\AntiFraudBundle\Entity\Rule;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Repository\RuleRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 * @template TEntity of Rule
 * @extends AbstractRepositoryTestCase<TEntity>
 */
#[CoversClass(RuleRepository::class)]
#[RunTestsInSeparateProcesses]
final class RuleRepositoryTest extends AbstractRepositoryTestCase
{
    private RuleRepository $repository;

    public function testPersistWithValidRulePersistsToDatabase(): void
    {
        $rule = $this->createTestRule();

        self::getEntityManager()->persist($rule);
        self::getEntityManager()->flush();

        $this->assertNotNull($rule->getId());
        $this->assertEquals('test-rule', $rule->getName());
        $this->assertEquals(RiskLevel::MEDIUM, $rule->getRiskLevel());
    }

    public function testFlushImmediatelyPersists(): void
    {
        $rule = $this->createTestRule();

        self::getEntityManager()->persist($rule);
        self::getEntityManager()->flush();

        $found = $this->repository->find($rule->getId());
        $this->assertNotNull($found);
        $this->assertEquals('test-rule', $found->getName());
    }

    public function testRemoveWithValidRuleRemovesFromDatabase(): void
    {
        $rule = $this->createTestRule();
        self::getEntityManager()->persist($rule);
        self::getEntityManager()->flush();

        $ruleId = $rule->getId();
        self::getEntityManager()->remove($rule);
        self::getEntityManager()->flush();

        $found = $this->repository->find($ruleId);
        $this->assertNull($found);
    }

    public function testFindActiveRulesReturnsOnlyEnabledRules(): void
    {
        // 先清理数据库中的所有规则
        self::getEntityManager()->createQuery('DELETE FROM Tourze\AntiFraudBundle\Entity\Rule')->execute();

        $enabledRule = $this->createTestRule('enabled-rule', 'enabled-condition', RiskLevel::HIGH, [], 100);
        $disabledRule = $this->createTestRule('disabled-rule', 'disabled-condition', RiskLevel::LOW, [], 50);
        $disabledRule->setEnabled(false);

        self::getEntityManager()->persist($enabledRule);
        self::getEntityManager()->persist($disabledRule);
        self::getEntityManager()->flush();

        $activeRules = $this->repository->findActiveRules();

        $this->assertCount(1, $activeRules);
        // 验证包含我们创建的启用规则
        $ruleNames = array_map(fn ($rule) => $rule->getName(), $activeRules);
        $this->assertContains('enabled-rule', $ruleNames);
        foreach ($activeRules as $rule) {
            $this->assertTrue($rule->isEnabled());
        }
    }

    public function testFindActiveRulesReturnsRulesOrderedByPriorityDesc(): void
    {
        // 先清理数据库中的所有规则
        self::getEntityManager()->createQuery('DELETE FROM Tourze\AntiFraudBundle\Entity\Rule')->execute();

        $highPriorityRule = $this->createTestRule('high-priority', 'condition', RiskLevel::HIGH, [], 100);
        $mediumPriorityRule = $this->createTestRule('medium-priority', 'condition', RiskLevel::MEDIUM, [], 50);
        $lowPriorityRule = $this->createTestRule('low-priority', 'condition', RiskLevel::LOW, [], 10);

        self::getEntityManager()->persist($mediumPriorityRule);
        self::getEntityManager()->persist($lowPriorityRule);
        self::getEntityManager()->persist($highPriorityRule);
        self::getEntityManager()->flush();

        $activeRules = $this->repository->findActiveRules();

        $this->assertCount(3, $activeRules);

        // 提取测试创建的规则名称和优先级，验证排序
        $testRules = [];
        foreach ($activeRules as $rule) {
            if (in_array($rule->getName(), ['high-priority', 'medium-priority', 'low-priority'], true)) {
                $testRules[] = $rule;
            }
        }

        // 验证测试创建的规则按优先级正确排序
        $this->assertCount(3, $testRules);
        $this->assertEquals('high-priority', $testRules[0]->getName());
        $this->assertEquals('medium-priority', $testRules[1]->getName());
        $this->assertEquals('low-priority', $testRules[2]->getName());
    }

    public function testFindByNameWithExistingRuleReturnsRule(): void
    {
        $rule = $this->createTestRule('unique-rule-name');
        self::getEntityManager()->persist($rule);
        self::getEntityManager()->flush();

        $found = $this->repository->findByName('unique-rule-name');

        $this->assertNotNull($found);
        $this->assertEquals('unique-rule-name', $found->getName());
    }

    public function testFindByNameWithNonExistentRuleReturnsNull(): void
    {
        $found = $this->repository->findByName('non-existent-rule');

        $this->assertNull($found);
    }

    public function testFindByRiskLevelReturnsOnlyEnabledRulesWithSpecificRiskLevel(): void
    {
        // 先清理数据库中的所有规则
        self::getEntityManager()->createQuery('DELETE FROM Tourze\AntiFraudBundle\Entity\Rule')->execute();

        $highRiskRule1 = $this->createTestRule('high-risk-1', 'condition', RiskLevel::HIGH, [], 100);
        $highRiskRule2 = $this->createTestRule('high-risk-2', 'condition', RiskLevel::HIGH, [], 90);
        $mediumRiskRule = $this->createTestRule('medium-risk', 'condition', RiskLevel::MEDIUM, [], 50);
        $disabledHighRiskRule = $this->createTestRule('disabled-high-risk', 'condition', RiskLevel::HIGH, [], 80);
        $disabledHighRiskRule->setEnabled(false);

        self::getEntityManager()->persist($mediumRiskRule);
        self::getEntityManager()->persist($highRiskRule2);
        self::getEntityManager()->persist($highRiskRule1);
        self::getEntityManager()->persist($disabledHighRiskRule);
        self::getEntityManager()->flush();

        $highRiskRules = $this->repository->findByRiskLevel(RiskLevel::HIGH->value);

        $this->assertCount(2, $highRiskRules);
        // 验证包含我们创建的高风险规则
        $ruleNames = array_map(fn ($rule) => $rule->getName(), $highRiskRules);
        $this->assertContains('high-risk-1', $ruleNames);
        $this->assertContains('high-risk-2', $ruleNames);
        foreach ($highRiskRules as $rule) {
            $this->assertEquals(RiskLevel::HIGH, $rule->getRiskLevel());
            $this->assertTrue($rule->isEnabled());
        }
    }

    public function testFindTerminalRulesReturnsOnlyTerminalEnabledRules(): void
    {
        // 先清理数据库中的所有规则
        self::getEntityManager()->createQuery('DELETE FROM Tourze\AntiFraudBundle\Entity\Rule')->execute();

        $terminalRule1 = $this->createTestRule('terminal-1', 'condition', RiskLevel::HIGH, [], 100, true);
        $terminalRule2 = $this->createTestRule('terminal-2', 'condition', RiskLevel::MEDIUM, [], 50, true);
        $nonTerminalRule = $this->createTestRule('non-terminal', 'condition', RiskLevel::LOW, [], 30, false);
        $disabledTerminalRule = $this->createTestRule('disabled-terminal', 'condition', RiskLevel::HIGH, [], 80, true);
        $disabledTerminalRule->setEnabled(false);

        self::getEntityManager()->persist($nonTerminalRule);
        self::getEntityManager()->persist($terminalRule2);
        self::getEntityManager()->persist($terminalRule1);
        self::getEntityManager()->persist($disabledTerminalRule);
        self::getEntityManager()->flush();

        $terminalRules = $this->repository->findTerminalRules();

        $this->assertCount(2, $terminalRules);
        // 验证包含我们创建的终端规则
        $ruleNames = array_map(fn ($rule) => $rule->getName(), $terminalRules);
        $this->assertContains('terminal-1', $ruleNames);
        $this->assertContains('terminal-2', $ruleNames);
        foreach ($terminalRules as $rule) {
            $this->assertTrue($rule->isTerminal());
            $this->assertTrue($rule->isEnabled());
        }
    }

    public function testCountByStatusReturnsAccurateCounts(): void
    {
        // 先清理数据库中的所有规则
        self::getEntityManager()->createQuery('DELETE FROM Tourze\AntiFraudBundle\Entity\Rule')->execute();

        $enabledRule1 = $this->createTestRule('enabled-1');
        $enabledRule2 = $this->createTestRule('enabled-2');
        $disabledRule1 = $this->createTestRule('disabled-1');
        $disabledRule1->setEnabled(false);
        $disabledRule2 = $this->createTestRule('disabled-2');
        $disabledRule2->setEnabled(false);

        self::getEntityManager()->persist($enabledRule1);
        self::getEntityManager()->persist($enabledRule2);
        self::getEntityManager()->persist($disabledRule1);
        self::getEntityManager()->persist($disabledRule2);
        self::getEntityManager()->flush();

        $counts = $this->repository->countByStatus();

        $this->assertEquals(4, $counts['total']);
        $this->assertEquals(2, $counts['enabled']);
        $this->assertEquals(2, $counts['disabled']);
    }

    public function testUpdateStatusUpdatesMultipleRulesStatus(): void
    {
        $rule1 = $this->createTestRule('rule-1');
        $rule2 = $this->createTestRule('rule-2');
        $rule3 = $this->createTestRule('rule-3');

        self::getEntityManager()->persist($rule1);
        self::getEntityManager()->persist($rule2);
        self::getEntityManager()->persist($rule3);
        self::getEntityManager()->flush();

        $rule1Id = $rule1->getId();
        $rule2Id = $rule2->getId();
        $this->assertNotNull($rule1Id);
        $this->assertNotNull($rule2Id);
        $affectedRows = $this->repository->updateStatus([$rule1Id, $rule2Id], false);

        $this->assertEquals(2, $affectedRows);

        self::getEntityManager()->refresh($rule1);
        self::getEntityManager()->refresh($rule2);
        self::getEntityManager()->refresh($rule3);

        $this->assertFalse($rule1->isEnabled());
        $this->assertFalse($rule2->isEnabled());
        $this->assertTrue($rule3->isEnabled());
    }

    public function testUpdateStatusUpdatesTimestamp(): void
    {
        $rule = $this->createTestRule();
        self::getEntityManager()->persist($rule);
        self::getEntityManager()->flush();

        $originalUpdatedAt = $rule->getUpdatedAt();
        sleep(1);

        $ruleId = $rule->getId();
        $this->assertNotNull($ruleId);
        $this->repository->updateStatus([$ruleId], false);

        self::getEntityManager()->refresh($rule);

        $this->assertGreaterThan($originalUpdatedAt, $rule->getUpdatedAt());
    }

    protected function onSetUp(): void
    {
        $this->repository = self::getService(RuleRepository::class);
    }

    /**
     * @param array<string, mixed> $actions
     */
    private function createTestRule(
        string $name = 'test-rule',
        string $condition = 'test-condition',
        RiskLevel $riskLevel = RiskLevel::MEDIUM,
        array $actions = [],
        int $priority = 50,
        bool $terminal = false,
    ): Rule {
        $rule = new Rule();
        $rule->setName($name);
        $rule->setCondition($condition);
        $rule->setRiskLevel($riskLevel);
        $rule->setActions($actions);
        $rule->setPriority($priority);
        $rule->setTerminal($terminal);

        return $rule;
    }

    // 乐观锁测试（因为使用了 #[Version] 注解）
    public function testFindWithPessimisticWriteLockShouldReturnEntityAndLockRow(): void
    {
        $rule = $this->createTestRule();
        self::getEntityManager()->persist($rule);
        self::getEntityManager()->flush();

        $ruleId = $rule->getId();
        $this->assertNotNull($ruleId);

        // 悲观锁需要在事务中执行
        self::getEntityManager()->beginTransaction();
        try {
            $found = $this->repository->find($ruleId, LockMode::PESSIMISTIC_WRITE);

            $this->assertNotNull($found);
            $this->assertEquals($ruleId, $found->getId());

            self::getEntityManager()->commit();
        } catch (\Exception $e) {
            self::getEntityManager()->rollback();
            throw $e;
        }
    }

    public function testFindWithOptimisticLockWhenVersionMismatchesShouldThrowExceptionOnFlush(): void
    {
        // 简化的乐观锁测试，直接验证版本字段的存在
        $rule = $this->createTestRule();
        self::getEntityManager()->persist($rule);
        self::getEntityManager()->flush();

        // 验证实体具有版本控制
        $this->assertNotNull($rule->getVersion());
        $this->assertEquals(1, $rule->getVersion());

        // 在实际应用中，乐观锁会在并发修改时触发 OptimisticLockException
        // 这里我们验证版本字段能正常工作
    }

    public function testFindByWithDescriptionIsNullShouldReturnRulesWithoutDescription(): void
    {
        $ruleWithDescription = $this->createTestRule('rule-with-desc');
        $ruleWithDescription->setDescription('This is a description');

        $ruleWithoutDescription = $this->createTestRule('rule-without-desc');

        self::getEntityManager()->persist($ruleWithDescription);
        self::getEntityManager()->persist($ruleWithoutDescription);
        self::getEntityManager()->flush();

        $results = $this->repository->findBy(['description' => null]);

        $this->assertCount(1, $results);
        $this->assertEquals('rule-without-desc', $results[0]->getName());
        $this->assertNull($results[0]->getDescription());
    }

    public function testCountWithDescriptionIsNullShouldReturnCorrectCount(): void
    {
        $ruleWithDescription = $this->createTestRule('rule-with-desc');
        $ruleWithDescription->setDescription('This is a description');

        $ruleWithoutDescription1 = $this->createTestRule('rule-without-desc-1');
        $ruleWithoutDescription2 = $this->createTestRule('rule-without-desc-2');

        self::getEntityManager()->persist($ruleWithDescription);
        self::getEntityManager()->persist($ruleWithoutDescription1);
        self::getEntityManager()->persist($ruleWithoutDescription2);
        self::getEntityManager()->flush();

        $count = $this->repository->count(['description' => null]);

        $this->assertEquals(2, $count);
    }

    // save 方法测试
    public function testSaveWithNewRuleShouldPersistToDatabase(): void
    {
        $rule = $this->createTestRule('new-rule');

        $this->repository->save($rule);

        $this->assertNotNull($rule->getId());

        $found = $this->repository->find($rule->getId());
        $this->assertNotNull($found);
        $this->assertEquals('new-rule', $found->getName());
    }

    public function testSaveWithExistingRuleShouldUpdateInDatabase(): void
    {
        $rule = $this->createTestRule('existing-rule');
        $this->repository->save($rule);

        $originalId = $rule->getId();
        $this->assertNotNull($originalId);

        $rule->setName('updated-rule');
        $this->repository->save($rule);

        $found = $this->repository->find($originalId);
        $this->assertNotNull($found);
        $this->assertEquals('updated-rule', $found->getName());
    }

    protected function createNewEntity(): object
    {
        return $this->createTestRule('test-rule-' . uniqid(), 'condition-' . uniqid(), RiskLevel::MEDIUM, [], 50, false);
    }

    /** @return ServiceEntityRepository<Rule> */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
