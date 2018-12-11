<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\EventListener\RfqEntityListener;
use Oro\Bundle\ConsentBundle\Extractor\CustomerUserExtractor;
use Oro\Bundle\ConsentBundle\Helper\GuestCustomerConsentAcceptancesHelper;
use Oro\Bundle\ConsentBundle\Queue\DelayedConsentAcceptancePersistQueueInterface;
use Oro\Bundle\ConsentBundle\Tests\Unit\Stub\ConsentAcceptanceStub;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\TestUtils\ORM\Mocks\UnitOfWork;

class RfqEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var UnitOfWork|\PHPUnit\Framework\MockObject\MockObject */
    private $uow;

    /** @var RfqEntityListener */
    private $eventListener;

    /** @var DelayedConsentAcceptancePersistQueueInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $delayedPersistQueue;

    /** @var GuestCustomerConsentAcceptancesHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $guestCustomerHelper;

    /** @var CustomerUserExtractor */
    private $customerUserExtractor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper */
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->guestCustomerHelper = $this->createMock(GuestCustomerConsentAcceptancesHelper::class);

        $this->uow = $this->createMock(UnitOfWork::class);

        $this->em = $this->createMock(EntityManager::class);
        $this->em
            ->expects($this->any())
            ->method('getUnitOfWork')
            ->willReturn($this->uow);

        $doctrineHelper
            ->expects($this->any())
            ->method('getEntityManagerForClass')
            ->with(ConsentAcceptance::class)
            ->willReturn($this->em);

        $doctrineHelper
            ->expects($this->any())
            ->method('getEntityClass')
            ->willReturnCallback(function ($entity) {
                if (is_object($entity)) {
                    return get_class($entity);
                }

                return null;
            });

        $this->delayedPersistQueue = $this->createMock(
            DelayedConsentAcceptancePersistQueueInterface::class
        );

        $this->customerUserExtractor = new CustomerUserExtractor($doctrineHelper);
        $this->customerUserExtractor->addMapping(Request::class, 'customerUser');

        $this->eventListener = new RfqEntityListener(
            $this->delayedPersistQueue,
            $doctrineHelper,
            $this->customerUserExtractor,
            $this->guestCustomerHelper
        );
    }

    /**
     * @dataProvider persistApplicableConsentAcceptanceProvider
     *
     * @param array $consentAcceptances
     * @param Request $rfq
     * @param CustomerUser|null $customerUser
     * @param bool $expectPersistCalling
     */
    public function testPersistApplicableConsentAcceptance(
        array $consentAcceptances,
        Request $rfq,
        CustomerUser $customerUser = null,
        bool $expectPersistCalling
    ) {
        if ($customerUser instanceof CustomerUser) {
            $this->delayedPersistQueue
                ->expects($this->once())
                ->method('getConsentAcceptancesByTrackedEntity')
                ->with($rfq)
                ->willReturn($consentAcceptances);
            $this->guestCustomerHelper->expects($this->once())
                ->method('filterGuestCustomerAcceptances')
                ->willReturnCallback(function ($customerUser, $acceptances) {
                    return $acceptances;
                });
        } else {
            $this->delayedPersistQueue
                ->expects($this->never())
                ->method('getConsentAcceptancesByTrackedEntity');
        }

        if ($expectPersistCalling) {
            $this->uow
                ->expects($this->once())
                ->method('computeChangeSets');

            $this->delayedPersistQueue
                ->expects($this->once())
                ->method('removeConsentAcceptancesByTrackedEntity')
                ->with($rfq);

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

            $this->uow
                ->expects($this->never())
                ->method('computeChangeSets');
        }

        $this->eventListener->persistApplicableConsentAcceptance($rfq);
    }

    /**
     * @return array
     */
    public function persistApplicableConsentAcceptanceProvider()
    {
        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 34]);
        $consentAcceptance = $this->getEntity(ConsentAcceptanceStub::class, ['id' => 1]);

        $rfqWithoutCustomerUser = $this->getEntity(Request::class, ['id' => 10]);
        $rfqWithCustomerUser = $this->getEntity(Request::class, [
            'id' => 10,
            'customerUser' => $customerUser
        ]);

        return [
            "No customer user in rfq" => [
                "consentAcceptances" => [],
                "rfq" => $rfqWithoutCustomerUser,
                "customerUser" => null,
                "expectPersistCalling" => false
            ],
            "Queue doesn't contain consentAcceptances by rfq" => [
                "consentAcceptances" => [],
                "rfq" => $rfqWithCustomerUser,
                "customerUser" => $customerUser,
                "expectPersistCalling" => false
            ],
            "Queue contains consentAcceptances by rfq" => [
                "consentAcceptances" => [ $consentAcceptance ],
                "rfq" => $rfqWithCustomerUser,
                "customerUser" => $customerUser,
                "expectPersistCalling" => true
            ]
        ];
    }
}
