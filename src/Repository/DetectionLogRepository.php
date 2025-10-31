<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\AntiFraudBundle\Entity\DetectionLog;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\Exception\InvalidIdentifierTypeException;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<DetectionLog>
 */
#[AsRepository(entityClass: DetectionLog::class)]
class DetectionLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DetectionLog::class);
    }

    /**
     * @return DetectionLog[]
     */
    public function findByUserId(string $userId, int $limit = 100): array
    {
        /** @var DetectionLog[] */
        return $this->createQueryBuilder('d')
            ->where('d.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('d.createdTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return DetectionLog[]
     */
    public function findByIpAddress(string $ipAddress, int $limit = 100): array
    {
        /** @var DetectionLog[] */
        return $this->createQueryBuilder('d')
            ->where('d.ipAddress = :ipAddress')
            ->setParameter('ipAddress', $ipAddress)
            ->orderBy('d.createdTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return DetectionLog[]
     */
    public function findBySessionId(string $sessionId, int $limit = 100): array
    {
        /** @var DetectionLog[] */
        return $this->createQueryBuilder('d')
            ->where('d.sessionId = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('d.createdTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return DetectionLog[]
     */
    public function findHighRiskLogs(\DateTimeImmutable $since, int $limit = 1000): array
    {
        /** @var DetectionLog[] */
        return $this->createQueryBuilder('d')
            ->where('d.riskLevel IN (:riskLevels)')
            ->andWhere('d.createdTime >= :since')
            ->setParameter('riskLevels', [RiskLevel::HIGH->value, RiskLevel::CRITICAL->value])
            ->setParameter('since', $since)
            ->orderBy('d.createdTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按风险等级统计指定时间段内的检测数量
     *
     * @return array<string, int>
     */
    public function countByRiskLevel(\DateTimeImmutable $since, ?\DateTimeImmutable $until = null): array
    {
        $qb = $this->createQueryBuilder('d')
            ->select('d.riskLevel, COUNT(d.id) as count')
            ->where('d.createdTime >= :since')
            ->setParameter('since', $since)
            ->groupBy('d.riskLevel')
        ;

        if (null !== $until) {
            $qb->andWhere('d.createdTime <= :until')
                ->setParameter('until', $until)
            ;
        }

        /** @var array<array{riskLevel: string|RiskLevel, count: string|int}> */
        $results = $qb->getQuery()->getResult();

        $counts = [];
        foreach ($results as $result) {
            $riskLevel = $result['riskLevel'];
            $key = $riskLevel instanceof RiskLevel ? $riskLevel->value : (string) $riskLevel;
            $counts[$key] = (int) $result['count'];
        }

        return $counts;
    }

    /**
     * 获取面板显示的最近检测记录
     *
     * @return DetectionLog[]
     */
    public function getRecentDetections(int $limit = 50): array
    {
        /** @var DetectionLog[] */
        return $this->createQueryBuilder('d')
            ->orderBy('d.createdTime', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 统计指定时间窗口内特定标识符的检测次数
     */
    public function countDetections(string $identifier, string $identifierType, int $seconds): int
    {
        $since = new \DateTimeImmutable("-{$seconds} seconds");

        $field = match ($identifierType) {
            'user' => 'd.userId',
            'ip' => 'd.ipAddress',
            'session' => 'd.sessionId',
            default => throw new InvalidIdentifierTypeException($identifierType),
        };

        return (int) $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where("{$field} = :identifier")
            ->andWhere('d.createdTime >= :since')
            ->setParameter('identifier', $identifier)
            ->setParameter('since', $since)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * 清理旧的日志记录
     */
    public function deleteOldLogs(\DateTimeImmutable $before): int
    {
        /** @var int */
        return $this->createQueryBuilder('d')
            ->delete()
            ->where('d.createdTime < :before')
            ->setParameter('before', $before)
            ->getQuery()
            ->execute()
        ;
    }

    public function save(DetectionLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(DetectionLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
