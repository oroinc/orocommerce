<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Handler;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Handler\SaveConsentAcceptanceHandler;
use Oro\Bundle\ConsentBundle\Provider\ConsentAcceptanceProvider;
use Oro\Bundle\ConsentBundle\Queue\DelayedConsentAcceptancePersistQueueInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Component\Testing\Unit\EntityTrait;

class SaveConsentAcceptanceHandlerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var DelayedConsentAcceptancePersistQueueInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $delayedPersistQueue;

    /** @var ConsentAcceptanceProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $consentAcceptanceProvider;

    /** @var SaveConsentAcceptanceHandler */
    private $handler;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->em
            ->expects($this->any())
            ->method('contains')
            ->willReturnCallback(function (CustomerUser $customerUser) {
                return null !== $customerUser->getId();
            });

        /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject $doctrineHelper */
        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper
            ->method('getEntityManagerForClass')
            ->with(ConsentAcceptance::class)
            ->willReturn($this->em);

        $this->delayedPersistQueue = $this->createMock(DelayedConsentAcceptancePersistQueueInterface::class);
        $this->consentAcceptanceProvider = $this->createMock(ConsentAcceptanceProvider::class);

        $this->handler = new SaveConsentAcceptanceHandler(
            $doctrineHelper,
            $this->delayedPersistQueue,
            $this->consentAcceptanceProvider
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->em);
        unset($this->delayedPersistQueue);
        unset($this->consentAcceptanceProvider);
        unset($this->handler);
    }

    /**
     * @dataProvider testSaveProvider
     *
     * @param bool                   $isGuest
     * @param array                  $selectedConsentAcceptances
     * @param array                  $customerConsentAcceptances
     * @param ConsentAcceptance|null $consentAcceptanceOnPersist
     * @param ConsentAcceptance|null $consentAcceptanceOnRemove
     * @param array                  $consentAcceptancesOnDelayedPersist
     */
    public function testSave(
        bool $isGuest,
        array $selectedConsentAcceptances,
        array $customerConsentAcceptances,
        ConsentAcceptance $consentAcceptanceOnPersist = null,
        ConsentAcceptance $consentAcceptanceOnRemove = null,
        array $consentAcceptancesOnDelayedPersist
    ) {
        /** @var CustomerUser $customerUser */
        if ($isGuest) {
            $customerUser = $this->getEntity(CustomerUser::class);
            $this->consentAcceptanceProvider
                ->expects($this->never())
                ->method('getCustomerConsentAcceptances');
        } else {
            $customerUser = $this->getEntity(CustomerUser::class, ['id' => 1]);
            $this->consentAcceptanceProvider
                ->expects($this->once())
                ->method('getCustomerConsentAcceptances')
                ->willReturn($customerConsentAcceptances);
        }

        if ($consentAcceptanceOnPersist) {
            $this->em
                ->expects($this->once())
                ->method('persist')
                ->with($consentAcceptanceOnPersist);
        } else {
            $this->em
                ->expects($this->never())
                ->method('persist');
        }

        if ($consentAcceptanceOnRemove) {
            $this->em
                ->expects($this->once())
                ->method('remove')
                ->with($consentAcceptanceOnRemove);
        } else {
            $this->em
                ->expects($this->never())
                ->method('remove');
        }

        if ($consentAcceptancesOnDelayedPersist) {
            $this->delayedPersistQueue
                ->expects($this->once())
                ->method('addConsentAcceptances')
                ->with($customerUser, $consentAcceptancesOnDelayedPersist);
        } else {
            $this->delayedPersistQueue
                ->expects($this->never())
                ->method('addConsentAcceptances');
        }

        $this->handler->save($customerUser, $selectedConsentAcceptances);
    }

    public function testSaveProvider()
    {
        $consentAcceptanceOnRemove = $this->getEntity(ConsentAcceptance::class, ['id' => 1]);
        $consentAcceptanceOnInsert = $this->getEntity(ConsentAcceptance::class);
        $customerUserConsentAcceptance = $this->getEntity(ConsentAcceptance::class, ['id' => 3]);

        return [
            "Has consent acceptance on remove with logged user" => [
                'isGuest' => false,
                'selectedConsentAcceptances' => [],
                'customerConsentAcceptances' => [$consentAcceptanceOnRemove],
                'consentAcceptanceOnPersist' => null,
                'consentAcceptanceOnRemove' => $consentAcceptanceOnRemove,
                'consentAcceptancesOnDelayedPersist' => []
            ],
            "Has consent acceptance on insert with logged user" => [
                'isGuest' => false,
                'selectedConsentAcceptances' => [$consentAcceptanceOnInsert],
                'customerConsentAcceptances' => [],
                'consentAcceptanceOnPersist' => $consentAcceptanceOnInsert,
                'consentAcceptanceOnRemove' => null,
                'consentAcceptancesOnDelayedPersist' => []
            ],
            "Has no consent acceptance on insert and remove with logged user" => [
                'isGuest' => false,
                'selectedConsentAcceptances' => [$customerUserConsentAcceptance],
                'customerConsentAcceptances' => [$customerUserConsentAcceptance],
                'consentAcceptanceOnPersist' => null,
                'consentAcceptanceOnRemove' => null,
                'consentAcceptancesOnDelayedPersist' => []
            ],
            "Has consent acceptance on insert with guest customer user" => [
                'isGuest' => true,
                'selectedConsentAcceptances' => [$consentAcceptanceOnInsert],
                'customerConsentAcceptances' => [],
                'consentAcceptanceOnPersist' => null,
                'consentAcceptanceOnRemove' => null,
                'consentAcceptancesOnDelayedPersist' => [$consentAcceptanceOnInsert]
            ],
        ];
    }
}
