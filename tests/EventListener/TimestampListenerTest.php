<?php

declare(strict_types=1);

namespace Tourze\AntiFraudBundle\Tests\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Tourze\AntiFraudBundle\Entity\RiskProfile;
use Tourze\AntiFraudBundle\Entity\Rule;
use Tourze\AntiFraudBundle\Enum\RiskLevel;
use Tourze\AntiFraudBundle\EventListener\TimestampListener;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(TimestampListener::class)]
#[RunTestsInSeparateProcesses]
final class TimestampListenerTest extends AbstractIntegrationTestCase
{
    private TimestampListener $listener;

    private PreUpdateEventArgs&MockObject $eventArgs;

    protected function onSetUp(): void
    {
        /** @var TimestampListener $listener */
        $listener = self::getContainer()->get(TimestampListener::class);
        $this->listener = $listener;
        $this->eventArgs = $this->createMock(PreUpdateEventArgs::class);
    }

    public function testPreUpdateWithRule(): void
    {
        $rule = new Rule();
        $rule->setName('Test Rule');
        $rule->setCondition('condition');
        $rule->setRiskLevel(RiskLevel::MEDIUM);
        $originalUpdatedAt = $rule->getUpdatedAt();

        // Sleep for a microsecond to ensure time difference
        usleep(1);

        $this->listener->preUpdate($rule, $this->eventArgs);

        $this->assertNotEquals($originalUpdatedAt, $rule->getUpdatedAt());
        $this->assertGreaterThan($originalUpdatedAt, $rule->getUpdatedAt());
    }

    public function testPreUpdateWithRiskProfile(): void
    {
        $riskProfile = new RiskProfile();
        $riskProfile->setIdentifierType('user');
        $riskProfile->setIdentifierValue('user123');
        $originalUpdatedAt = $riskProfile->getUpdatedAt();

        // Sleep for a microsecond to ensure time difference
        usleep(1);

        $this->listener->preUpdate($riskProfile, $this->eventArgs);

        $this->assertNotEquals($originalUpdatedAt, $riskProfile->getUpdatedAt());
        $this->assertGreaterThan($originalUpdatedAt, $riskProfile->getUpdatedAt());
    }
}
