<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Tourze\AntiFraudBundle\Entity\RiskProfile;
use Tourze\AntiFraudBundle\Entity\Rule;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: RiskProfile::class)]
#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Rule::class)]
class TimestampListener
{
    public function preUpdate(RiskProfile|Rule $entity, PreUpdateEventArgs $event): void
    {
        $entity->setUpdatedTime(new \DateTimeImmutable());
    }
}
