<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Queue;

use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Queue\DelayedConsentAcceptancePersistQueue;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class DelayedConsentAcceptancePersistQueueTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var DelayedConsentAcceptancePersistQueue */
    private $delayedPersistQueue;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->delayedPersistQueue = new DelayedConsentAcceptancePersistQueue($this->logger);
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->logger);
        unset($this->delayedPersistQueue);
    }

    public function testTryToAddConsentAcceptancesWithNotSupportedTrackedEntity()
    {
        $this->delayedPersistQueue->setSupportedEntityClassName(CustomerUser::class);
        $this->logger
            ->expects($this->once())
            ->method('warning')
            ->with(
                'Expected that argument $trackedEntity will be instance '.
                'of "Oro\Bundle\CustomerBundle\Entity\CustomerUser", '.
                'but got "Oro\Bundle\RFPBundle\Entity\Request".'
            );

        $trackedEntity = $this->getEntity(Request::class, ['id' => 1]);
        $consentAcceptance = $this->getEntity(ConsentAcceptance::class, ['id' => 1]);
        $this->delayedPersistQueue->addConsentAcceptances(
            $trackedEntity,
            [$consentAcceptance]
        );
    }

    public function testRemoveConsentAcceptancesByTrackedEntity()
    {
        $this->delayedPersistQueue->setSupportedEntityClassName(CustomerUser::class);

        $trackedEntity = $this->getEntity(CustomerUser::class, ['id' => 1]);
        $consentAcceptance = $this->getEntity(ConsentAcceptance::class, ['id' => 1]);
        $this->delayedPersistQueue->addConsentAcceptances(
            $trackedEntity,
            [$consentAcceptance]
        );

        $consentAcceptancesFromQueue = $this->delayedPersistQueue->getConsentAcceptancesByTrackedEntity(
            $trackedEntity
        );
        $this->assertSame([$consentAcceptance], $consentAcceptancesFromQueue);

        $this->delayedPersistQueue->removeConsentAcceptancesByTrackedEntity($trackedEntity);
        $consentAcceptancesFromQueueAfterRemoving = $this->delayedPersistQueue->getConsentAcceptancesByTrackedEntity(
            $trackedEntity
        );
        $this->assertSame([], $consentAcceptancesFromQueueAfterRemoving);
    }

    public function testRemoveConsentAcceptancesByTrackedEntityInvalidEntity()
    {
        $this->assertFalse($this->delayedPersistQueue->removeConsentAcceptancesByTrackedEntity('string'));
    }

    public function testRemoveConsentAcceptancesByTrackedEntityNoTrackedEntityKey()
    {
        $this->delayedPersistQueue->setSupportedEntityClassName(CustomerUser::class);

        $trackedEntity = $this->getEntity(CustomerUser::class, ['id' => 1]);
        $nonTrackedEntity = $this->getEntity(CustomerUser::class, ['id' => 2]);

        $consentAcceptance1 = $this->getEntity(ConsentAcceptance::class, ['id' => 1]);
        $this->delayedPersistQueue->addConsentAcceptances(
            $trackedEntity,
            [$consentAcceptance1]
        );

        $consentAcceptance2 = $this->getEntity(ConsentAcceptance::class, ['id' => 2]);
        $this->delayedPersistQueue->addConsentAcceptances(
            $trackedEntity,
            [$consentAcceptance1, $consentAcceptance2]
        );

        $this->assertFalse($this->delayedPersistQueue->removeConsentAcceptancesByTrackedEntity($nonTrackedEntity));
    }

    public function testGetConsentAcceptancesByTrackedEntityWithoutAnyRecordsInQueue()
    {
        $this->delayedPersistQueue->setSupportedEntityClassName(CustomerUser::class);
        $trackedEntity = $this->getEntity(CustomerUser::class, ['id' => 1]);
        $consentAcceptancesFromQueue = $this->delayedPersistQueue->getConsentAcceptancesByTrackedEntity(
            $trackedEntity
        );
        $this->assertSame([], $consentAcceptancesFromQueue);
    }

    public function testGetConsentAcceptancesByTrackedEntityWithOnlyAddedRecordInQueue()
    {
        $this->delayedPersistQueue->setSupportedEntityClassName(CustomerUser::class);

        $trackedEntity = $this->getEntity(CustomerUser::class, ['id' => 1]);
        $consentAcceptance = $this->getEntity(ConsentAcceptance::class, ['id' => 1]);
        $this->delayedPersistQueue->addConsentAcceptances(
            $trackedEntity,
            [$consentAcceptance]
        );

        $consentAcceptancesFromQueue = $this->delayedPersistQueue->getConsentAcceptancesByTrackedEntity(
            $trackedEntity
        );
        $this->assertSame([$consentAcceptance], $consentAcceptancesFromQueue);
    }

    public function testGetConsentAcceptancesByTrackedEntityWithSeveralRecordsInQueue()
    {
        $this->delayedPersistQueue->setSupportedEntityClassName(CustomerUser::class);

        $trackedEntity = $this->getEntity(CustomerUser::class, ['id' => 1]);
        $consentAcceptance1 = $this->getEntity(ConsentAcceptance::class, ['id' => 1]);
        $this->delayedPersistQueue->addConsentAcceptances(
            $trackedEntity,
            [$consentAcceptance1]
        );

        $consentAcceptance2 = $this->getEntity(ConsentAcceptance::class, ['id' => 2]);
        $this->delayedPersistQueue->addConsentAcceptances(
            $trackedEntity,
            [$consentAcceptance1, $consentAcceptance2]
        );

        $consentAcceptancesFromQueue = $this->delayedPersistQueue->getConsentAcceptancesByTrackedEntity(
            $trackedEntity
        );
        $this->assertSame([$consentAcceptance1, $consentAcceptance2], $consentAcceptancesFromQueue);
    }

    public function testGetConsentAcceptancesByTrackedEntityInvalidEntity()
    {
        $this->assertSame([], $this->delayedPersistQueue->getConsentAcceptancesByTrackedEntity('string'));
    }

    public function testGetConsentAcceptancesByTrackedEntityNoTrackedEntityKey()
    {
        $this->delayedPersistQueue->setSupportedEntityClassName(CustomerUser::class);

        $trackedEntity = $this->getEntity(CustomerUser::class, ['id' => 1]);
        $nonTrackedEntity = $this->getEntity(CustomerUser::class, ['id' => 2]);

        $consentAcceptance1 = $this->getEntity(ConsentAcceptance::class, ['id' => 1]);
        $this->delayedPersistQueue->addConsentAcceptances(
            $trackedEntity,
            [$consentAcceptance1]
        );

        $consentAcceptance2 = $this->getEntity(ConsentAcceptance::class, ['id' => 2]);
        $this->delayedPersistQueue->addConsentAcceptances(
            $trackedEntity,
            [$consentAcceptance1, $consentAcceptance2]
        );

        $this->assertSame([], $this->delayedPersistQueue->getConsentAcceptancesByTrackedEntity($nonTrackedEntity));
    }
}
