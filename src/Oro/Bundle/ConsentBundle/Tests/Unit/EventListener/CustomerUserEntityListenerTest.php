<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\EventListener\CustomerUserEntityListener;
use Oro\Bundle\ConsentBundle\Queue\DelayedConsentAcceptancePersistQueueInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\Unit\EntityTrait;

class CustomerUserEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var EntityManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $em;

    /**
     * @var CustomerUserEntityListener
     */
    private $eventListener;

    /**
     * @var DelayedConsentAcceptancePersistQueueInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $delayedPersistQueue;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper */
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->em = $this->createMock(EntityManager::class);
        $doctrineHelper
            ->expects($this->any())
            ->method('getEntityManagerForClass')
            ->with(ConsentAcceptance::class)
            ->willReturn($this->em);

        $this->delayedPersistQueue = $this->createMock(
            DelayedConsentAcceptancePersistQueueInterface::class
        );

        $this->eventListener = new CustomerUserEntityListener(
            $this->delayedPersistQueue,
            $doctrineHelper
        );
    }

    /**
     * @dataProvider persistApplicableConsentAcceptanceProvider
     *
     * @param array $consentAcceptances
     * @param CustomerUser $customerUser
     * @param bool $expectPersistCalling
     */
    public function testPersistApplicableConsentAcceptance(
        array $consentAcceptances,
        CustomerUser $customerUser,
        bool $expectPersistCalling
    ) {
        $this->delayedPersistQueue
            ->expects($this->once())
            ->method('getConsentAcceptancesByTrackedEntity')
            ->with($customerUser)
            ->willReturn($consentAcceptances);

        if ($expectPersistCalling) {
            $this->delayedPersistQueue
                ->expects($this->once())
                ->method('removeConsentAcceptancesByTrackedEntity')
                ->with($customerUser);

            $this->em
                ->expects($this->once())
                ->method('persist')
                ->with(current($consentAcceptances));
        } else {
            $this->delayedPersistQueue
                ->expects($this->never())
                ->method('removeConsentAcceptancesByTrackedEntity')
                ->willReturn($consentAcceptances);

            $this->em
                ->expects($this->never())
                ->method('persist');
        }

        $this->eventListener->persistApplicableConsentAcceptance($customerUser);
    }

    /**
     * @return array
     */
    public function persistApplicableConsentAcceptanceProvider()
    {
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 34]);
        $consentAcceptance = $this->getEntity(ConsentAcceptance::class, [
            'id' => 1,
            'customerUser' => $customerUser
        ]);

        return [
            "Queue is empty" => [
                "consentAcceptances" => [],
                "customerUser" => $customerUser,
                "expectPersistCalling" => false
            ],
            "Queue contains consentAcceptance, but it signed by another customerUser" => [
                "consentAcceptances" => [],
                "customerUser" => $customerUser,
                "expectPersistCalling" => false
            ],
            "Queue contains consentAcceptance and it signed by another customerUser" => [
                "consentAcceptances" => [$consentAcceptance],
                "customerUser" => $customerUser,
                "expectPersistCalling" => true
            ]
        ];
    }
}
