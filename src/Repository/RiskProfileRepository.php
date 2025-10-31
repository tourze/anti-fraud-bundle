<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\AntiFraudBundle\Entity\RiskProfile;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<RiskProfile>
 */
#[AsRepository(entityClass: RiskProfile::class)]
class RiskProfileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RiskProfile::class);
    }

    public function findByIdentifier(string $type, string $value): ?RiskProfile
    {
        /** @var RiskProfile|null */
        return $this->createQueryBuilder('r')
            ->where('r.identifierType = :type')
            ->andWhere('r.identifierValue = :value')
            ->setParameter('type', $type)
            ->setParameter('value', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOrCreate(string $type, string $value): RiskProfile
    {
        $profile = $this->findByIdentifier($type, $value);

        if (null === $profile) {
            $profile = new RiskProfile();
            $profile->setIdentifierType($type);
            $profile->setIdentifierValue($value);
            $this->getEntityManager()->persist($profile);
        }

        return $profile;
    }

    /**
     * @return RiskProfile[]
     */
    public function findHighRiskProfiles(int $limit = 100): array
    {
        /** @var RiskProfile[] */
        return $this->createQueryBuilder('r')
            ->where('r.riskLevel IN (:riskLevels)')
            ->andWhere('r.isWhitelisted = :whitelisted')
            ->setParameter('riskLevels', [RiskLevel::HIGH->value, RiskLevel::CRITICAL->value])
            ->setParameter('whitelisted', false)
            ->orderBy('r.riskScore', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return RiskProfile[]
     */
    public function findBlacklisted(): array
    {
        /** @var RiskProfile[] */
        return $this->createQueryBuilder('r')
            ->where('r.isBlacklisted = :blacklisted')
            ->setParameter('blacklisted', true)
            ->orderBy('r.updatedTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return RiskProfile[]
     */
    public function findWhitelisted(): array
    {
        /** @var RiskProfile[] */
        return $this->createQueryBuilder('r')
            ->where('r.isWhitelisted = :whitelisted')
            ->setParameter('whitelisted', true)
            ->orderBy('r.updatedTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按风险等级统计概况数量
     */
    /**
     * @return array<string, int>
     */
    public function countByRiskLevel(): array
    {
        /** @var array<array{riskLevel: string|RiskLevel, count: string|int}> */
        $results = $this->createQueryBuilder('r')
            ->select('r.riskLevel, COUNT(r.id) as count')
            ->groupBy('r.riskLevel')
            ->getQuery()
            ->getResult()
        ;

        $counts = [];
        foreach ($results as $result) {
            $riskLevel = $result['riskLevel'];
            $key = $riskLevel instanceof RiskLevel ? $riskLevel->value : (string) $riskLevel;
            $counts[$key] = (int) $result['count'];
        }

        return $counts;
    }

    /**
     * 查找最近有活动的概况
     */
    /**
     * @return RiskProfile[]
     */
    public function findRecentlyActive(\DateTimeImmutable $since, int $limit = 100): array
    {
        /** @var RiskProfile[] */
        return $this->createQueryBuilder('r')
            ->where('r.lastDetectionTime >= :since')
            ->setParameter('since', $since)
            ->orderBy('r.lastDetectionTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 清理非活动的概况
     */
    public function deleteInactiveProfiles(\DateTimeImmutable $before): int
    {
        /** @var int */
        return $this->createQueryBuilder('r')
            ->delete()
            ->where('r.lastDetectionTime < :before OR r.lastDetectionTime IS NULL')
            ->andWhere('r.totalDetections = 0')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute()
        ;
    }

    public function save(RiskProfile $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(RiskProfile $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
