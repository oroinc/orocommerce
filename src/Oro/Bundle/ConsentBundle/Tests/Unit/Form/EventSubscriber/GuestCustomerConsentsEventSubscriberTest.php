<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Form\EventSubscriber\GuestCustomerConsentsEventSubscriber;
use Oro\Bundle\ConsentBundle\Form\Type\CustomerConsentsType;
use Oro\Bundle\ConsentBundle\Queue\DelayedConsentAcceptancePersistQueueInterface as DelayedPersistQueueInterface;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class GuestCustomerConsentsEventSubscriberTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var GuestCustomerConsentsEventSubscriber */
    private $eventSubscriber;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->eventSubscriber = new GuestCustomerConsentsEventSubscriber();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->eventSubscriber,
            $this->guestCustomerHelper
        );
    }

    public function testSaveConsentAcceptancesWithInvalidData()
    {
        /** @var DelayedPersistQueueInterface|\PHPUnit\Framework\MockObject\MockObject $delayedPersistQueue */
        $delayedPersistQueue = $this->createMock(DelayedPersistQueueInterface::class);
        $delayedPersistQueue
            ->expects($this->never())
            ->method('isEntitySupported');

        $delayedPersistQueue
            ->expects($this->never())
            ->method('addConsentAcceptances');

        $this->eventSubscriber->addDelayedPersistQueue($delayedPersistQueue);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->never())
            ->method('has')
            ->with(CustomerConsentsType::TARGET_FIELDNAME);

        $event = new FormEvent($form, null);

        $this->eventSubscriber->saveConsentAcceptances($event);
    }

    public function testSaveConsentAcceptancesButNoApplicableQueue()
    {
        $rfq = $this->getEntity(Request::class, ['id' => 1]);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->never())
            ->method('has')
            ->with(CustomerConsentsType::TARGET_FIELDNAME);

        $event = new FormEvent($form, $rfq);

        /** @var DelayedPersistQueueInterface|\PHPUnit\Framework\MockObject\MockObject $delayedPersistQueue */
        $delayedPersistQueue = $this->createMock(DelayedPersistQueueInterface::class);
        $delayedPersistQueue
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($rfq)
            ->willReturn(false);

        $delayedPersistQueue
            ->expects($this->never())
            ->method('addConsentAcceptances');

        $this->eventSubscriber->addDelayedPersistQueue($delayedPersistQueue);
        $this->eventSubscriber->saveConsentAcceptances($event);
    }

    public function testSaveConsentAcceptancesButFormIsInvalid()
    {
        $rfq = $this->getEntity(Request::class, ['id' => 1]);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('has')
            ->with(CustomerConsentsType::TARGET_FIELDNAME)
            ->willReturn(true);

        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $event = new FormEvent($form, $rfq);

        /** @var DelayedPersistQueueInterface|\PHPUnit\Framework\MockObject\MockObject $delayedPersistQueue */
        $delayedPersistQueue = $this->createMock(DelayedPersistQueueInterface::class);
        $delayedPersistQueue
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($rfq)
            ->willReturn(true);

        $delayedPersistQueue
            ->expects($this->never())
            ->method('addConsentAcceptances');

        $this->eventSubscriber->addDelayedPersistQueue($delayedPersistQueue);
        $this->eventSubscriber->saveConsentAcceptances($event);
    }

    public function testSaveConsentAcceptancesButNoConsentAcceptances()
    {
        $rfq = $this->getEntity(Request::class, ['id' => 1]);

        $event = new FormEvent($this->getPreparedFormType(), $rfq);

        /** @var DelayedPersistQueueInterface|\PHPUnit\Framework\MockObject\MockObject $delayedPersistQueue */
        $delayedPersistQueue = $this->createMock(DelayedPersistQueueInterface::class);
        $delayedPersistQueue
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($rfq)
            ->willReturn(true);

        $delayedPersistQueue
            ->expects($this->never())
            ->method('addConsentAcceptances');

        $this->eventSubscriber->addDelayedPersistQueue($delayedPersistQueue);
        $this->eventSubscriber->saveConsentAcceptances($event);
    }

    public function testSaveConsentAcceptances()
    {
        $rfq = $this->getEntity(Request::class, ['id' => 1]);
        $consentAcceptance = $this->getEntity(ConsentAcceptance::class, ['id' => 10]);

        $event = new FormEvent($this->getPreparedFormType([$consentAcceptance]), $rfq);

        /** @var DelayedPersistQueueInterface|\PHPUnit\Framework\MockObject\MockObject $delayedPersistQueue */
        $delayedPersistQueue = $this->createMock(DelayedPersistQueueInterface::class);
        $delayedPersistQueue
            ->expects($this->once())
            ->method('isEntitySupported')
            ->with($rfq)
            ->willReturn(true);

        $delayedPersistQueue
            ->expects($this->once())
            ->method('addConsentAcceptances')
            ->with($rfq, [$consentAcceptance]);

        $this->eventSubscriber->addDelayedPersistQueue($delayedPersistQueue);
        $this->eventSubscriber->saveConsentAcceptances($event);
    }

    /**
     * @param array|null $customerConsentsTypeData
     *
     * @return \PHPUnit\Framework\MockObject\MockObject|FormInterface
     */
    private function getPreparedFormType(array $customerConsentsTypeData = null)
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $customerConsentsType */
        $customerConsentsType = $this->createMock(FormInterface::class);
        $customerConsentsType
            ->expects($this->once())
            ->method('getData')
            ->willReturn($customerConsentsTypeData);

        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
        $form = $this->createMock(FormInterface::class);
        $form->expects($this->once())
            ->method('has')
            ->with(CustomerConsentsType::TARGET_FIELDNAME)
            ->willReturn(true);

        $form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $form->expects($this->once())
            ->method('get')
            ->with(CustomerConsentsType::TARGET_FIELDNAME)
            ->willReturn($customerConsentsType);

        return $form;
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [FormEvents::POST_SUBMIT => 'saveConsentAcceptances'],
            GuestCustomerConsentsEventSubscriber::getSubscribedEvents()
        );
    }
}
