<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\AntiFraudBundle\Entity\Rule;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<Rule>
 */
#[AsRepository(entityClass: Rule::class)]
class RuleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rule::class);
    }

    /**
     * @return Rule[]
     */
    public function findActiveRules(): array
    {
        /** @var Rule[] */
        return $this->createQueryBuilder('r')
            ->where('r.enabled = :enabled')
            ->setParameter('enabled', true)
            ->orderBy('r.priority', 'DESC')
            ->addOrderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByName(string $name): ?Rule
    {
        /** @var Rule|null */
        return $this->createQueryBuilder('r')
            ->where('r.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @return Rule[]
     */
    public function findByRiskLevel(string $riskLevel): array
    {
        /** @var Rule[] */
        return $this->createQueryBuilder('r')
            ->where('r.riskLevel = :riskLevel')
            ->andWhere('r.enabled = :enabled')
            ->setParameter('riskLevel', $riskLevel)
            ->setParameter('enabled', true)
            ->orderBy('r.priority', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return Rule[]
     */
    public function findTerminalRules(): array
    {
        /** @var Rule[] */
        return $this->createQueryBuilder('r')
            ->where('r.terminal = :terminal')
            ->andWhere('r.enabled = :enabled')
            ->setParameter('terminal', true)
            ->setParameter('enabled', true)
            ->orderBy('r.priority', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按状态统计规则数量
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        $qb = $this->createQueryBuilder('r');

        return [
            'total' => (int) $qb->select('COUNT(r.id)')
                ->getQuery()
                ->getSingleScalarResult(),
            'enabled' => (int) $qb->select('COUNT(r.id)')
                ->where('r.enabled = :enabled')
                ->setParameter('enabled', true)
                ->getQuery()
                ->getSingleScalarResult(),
            'disabled' => (int) $qb->select('COUNT(r.id)')
                ->where('r.enabled = :enabled')
                ->setParameter('enabled', false)
                ->getQuery()
                ->getSingleScalarResult(),
        ];
    }

    /**
     * 批量更新规则状态
     * @param array<int> $ruleIds
     */
    public function updateStatus(array $ruleIds, bool $enabled): int
    {
        /** @var int */
        return $this->createQueryBuilder('r')
            ->update()
            ->set('r.enabled', ':enabled')
            ->set('r.updatedTime', ':updatedAt')
            ->where('r.id IN (:ids)')
            ->setParameter('enabled', $enabled)
            ->setParameter('updatedAt', new \DateTimeImmutable())
            ->setParameter('ids', $ruleIds)
            ->getQuery()
            ->execute()
        ;
    }

    public function save(Rule $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Rule $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
