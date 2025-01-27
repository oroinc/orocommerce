<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\EventListener\CompletedCheckoutEventListener;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Event\Transition\PreAnnounceEvent;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CompletedCheckoutEventListenerTest extends TestCase
{
    private UrlGeneratorInterface&MockObject $urlGenerator;
    private CompletedCheckoutEventListener $listener;

    protected function setUp(): void
    {
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->listener = new CompletedCheckoutEventListener($this->urlGenerator);
    }

    private function getWorkflowItem(array $metadata = [], ?object $entity = null): WorkflowItem
    {
        $workflowDefinition = $this->createMock(WorkflowDefinition::class);
        $workflowDefinition->expects(self::once())
            ->method('getMetadata')
            ->willReturn($metadata);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects(self::once())
            ->method('getDefinition')
            ->willReturn($workflowDefinition);
        if (null === $entity) {
            $workflowItem->expects(self::never())
                ->method('getEntity');
        } else {
            $workflowItem->expects(self::once())
                ->method('getEntity')
                ->willReturn($entity);
        }

        return $workflowItem;
    }

    private function getWorkflowTransition(array $frontendOptions = []): Transition
    {
        $workflowTransition = $this->createMock(Transition::class);
        $workflowTransition->expects(self::any())
            ->method('getFrontendOptions')
            ->willReturn($frontendOptions);

        return $workflowTransition;
    }

    private function getOrder(int $id, string $identifier): Order
    {
        $order = new Order();
        ReflectionUtil::setId($order, $id);
        $order->setIdentifier($identifier);

        return $order;
    }

    public function testOnPreAnnounceWhenNoErrorCollection(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects(self::never())
            ->method('getDefinition');
        $workflowItem->expects(self::never())
            ->method('getEntity');
        $workflowTransition = $this->createMock(Transition::class);
        $workflowTransition->expects(self::never())
            ->method('getFrontendOptions');

        $this->urlGenerator->expects(self::never())
            ->method('generate');

        $this->listener->onPreAnnounce(new PreAnnounceEvent($workflowItem, $workflowTransition, true));
    }

    public function testOnPreAnnounceNotCheckoutWorkflow(): void
    {
        $workflowItem = $this->getWorkflowItem();
        $workflowTransition = $this->getWorkflowTransition();
        $errors = new ArrayCollection();

        $this->listener->onPreAnnounce(new PreAnnounceEvent($workflowItem, $workflowTransition, true, $errors));

        self::assertCount(0, $errors);
    }

    public function testOnPreAnnounceMissingFrontendOption(): void
    {
        $workflowItem = $this->getWorkflowItem(['is_checkout_workflow' => true]);
        $workflowTransition = $this->getWorkflowTransition();
        $errors = new ArrayCollection();

        $this->listener->onPreAnnounce(new PreAnnounceEvent($workflowItem, $workflowTransition, true, $errors));

        self::assertCount(0, $errors);
    }

    public function testOnPreAnnounceIncompleteCheckout(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::once())
            ->method('isCompleted')
            ->willReturn(false);
        $workflowItem = $this->getWorkflowItem(['is_checkout_workflow' => true], $checkout);
        $workflowTransition = $this->getWorkflowTransition(['is_checkout_continue' => true]);
        $errors = new ArrayCollection();

        $this->listener->onPreAnnounce(new PreAnnounceEvent($workflowItem, $workflowTransition, true, $errors));

        self::assertCount(0, $errors);
    }

    public function testOnPreAnnounceAddsError(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $checkout->expects(self::any())
            ->method('isCompleted')
            ->willReturn(true);
        $checkout->expects(self::any())
            ->method('getOrder')
            ->willReturn($this->getOrder(42, 'ORDER-42'));

        $workflowItem = $this->getWorkflowItem(['is_checkout_workflow' => true], $checkout);
        $workflowTransition = $this->getWorkflowTransition(['is_checkout_continue' => true]);
        $errors = new ArrayCollection();

        $this->urlGenerator->expects(self::once())
            ->method('generate')
            ->with('oro_order_frontend_view', ['id' => 42])
            ->willReturn('/order/view/42');

        $this->listener->onPreAnnounce(new PreAnnounceEvent($workflowItem, $workflowTransition, true, $errors));

        self::assertEquals(
            [
                [
                    'message' => 'oro.checkout.workflow.condition.completed_workflow.message',
                    'parameters' => [
                        '%orderViewLink%' => '/order/view/42',
                        '%orderIdentifier%' => 'ORDER-42'
                    ]
                ]
            ],
            $errors->toArray()
        );
    }
}
