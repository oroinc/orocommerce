<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionAfterEvent;
use Oro\Bundle\CheckoutBundle\Event\CheckoutTransitionBeforeEvent;
use Oro\Bundle\CheckoutBundle\EventListener\CheckoutTransitionEventSubscriber;
use Oro\Bundle\CheckoutBundle\WorkflowState\Manager\CheckoutStateDiffManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowResult;

class CheckoutTransitionEventSubscriberTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutStateDiffManager|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutStateDiffManager;

    private CheckoutTransitionEventSubscriber $eventSubscriber;

    protected function setUp(): void
    {
        $this->checkoutStateDiffManager = $this->createMock(CheckoutStateDiffManager::class);
        $this->eventSubscriber = new CheckoutTransitionEventSubscriber($this->checkoutStateDiffManager);
    }

    public function testGetSubscribedEvents(): void
    {
        $this->assertEquals(
            [
                CheckoutTransitionBeforeEvent::class => 'onBefore',
                CheckoutTransitionAfterEvent::class => 'onAfter',
            ],
            CheckoutTransitionEventSubscriber::getSubscribedEvents()
        );
    }

    public function testOnBefore(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $event = new CheckoutTransitionBeforeEvent($workflowItem, $this->createMock(Transition::class));

        $checkout = $this->createMock(Checkout::class);
        $workflowItem
            ->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $sampleState = ['sample_state_key' => 'sample_state_value'];
        $this->checkoutStateDiffManager
            ->expects($this->once())
            ->method('getCurrentState')
            ->with($checkout)
            ->willReturn($sampleState);

        $workflowResult = $this->createMock(WorkflowResult::class);
        $workflowItem
            ->expects($this->once())
            ->method('getResult')
            ->willReturn($workflowResult);

        $workflowResult
            ->expects($this->once())
            ->method('set')
            ->with('currentCheckoutState', $sampleState);

        $this->eventSubscriber->onBefore($event);
    }

    public function testOnAfter(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $event = new CheckoutTransitionAfterEvent(
            $workflowItem,
            $this->createMock(Transition::class),
            true,
            new ArrayCollection()
        );

        $workflowResult = $this->createMock(WorkflowResult::class);
        $workflowItem
            ->expects($this->once())
            ->method('getResult')
            ->willReturn($workflowResult);

        $workflowResult
            ->expects($this->once())
            ->method('remove')
            ->with('currentCheckoutState');

        $this->eventSubscriber->onAfter($event);
    }
}
