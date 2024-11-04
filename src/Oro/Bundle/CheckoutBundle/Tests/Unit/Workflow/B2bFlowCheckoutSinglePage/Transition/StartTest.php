<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckoutSinglePage\Transition;

use Oro\Bundle\CheckoutBundle\Action\DefaultPaymentMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\CustomerUserActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckoutSinglePage\Transition\Start;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class StartTest extends TestCase
{
    private CustomerUserActionsInterface|MockObject $customerUserActions;
    private DefaultShippingMethodSetterInterface|MockObject $defaultShippingMethodSetter;
    private DefaultPaymentMethodSetterInterface|MockObject $defaultPaymentMethodSetter;
    private TransitionServiceInterface|MockObject $baseTransition;
    private Start $start;

    #[\Override]
    protected function setUp(): void
    {
        $this->customerUserActions = $this->createMock(CustomerUserActionsInterface::class);
        $this->defaultShippingMethodSetter = $this->createMock(DefaultShippingMethodSetterInterface::class);
        $this->defaultPaymentMethodSetter = $this->createMock(DefaultPaymentMethodSetterInterface::class);
        $this->baseTransition = $this->createMock(TransitionServiceInterface::class);

        $this->start = new Start(
            $this->customerUserActions,
            $this->defaultShippingMethodSetter,
            $this->defaultPaymentMethodSetter,
            $this->baseTransition
        );
    }

    public function testExecute(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = $this->createMock(Checkout::class);

        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $this->baseTransition->expects($this->once())
            ->method('execute')
            ->with($workflowItem);

        $this->customerUserActions->expects($this->once())
            ->method('createGuestCustomerUser')
            ->with($checkout);

        $this->defaultShippingMethodSetter->expects($this->once())
            ->method('setDefaultShippingMethod')
            ->with($checkout);

        $this->defaultPaymentMethodSetter->expects($this->once())
            ->method('setDefaultPaymentMethod')
            ->with($checkout);

        $this->start->execute($workflowItem);
    }
}
