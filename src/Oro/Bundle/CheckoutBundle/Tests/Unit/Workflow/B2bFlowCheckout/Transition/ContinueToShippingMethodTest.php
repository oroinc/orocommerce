<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\AddressActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\ShippingMethodActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition\ContinueToShippingMethod;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContinueToShippingMethodTest extends TestCase
{
    private AddressActionsInterface|MockObject $addressActions;
    private ShippingMethodActionsInterface|MockObject $shippingMethodActions;
    private TransitionServiceInterface|MockObject $baseContinueTransition;
    private ContinueToShippingMethod $transition;

    #[\Override]
    protected function setUp(): void
    {
        $this->addressActions = $this->createMock(AddressActionsInterface::class);
        $this->shippingMethodActions = $this->createMock(ShippingMethodActionsInterface::class);
        $this->baseContinueTransition = $this->createMock(TransitionServiceInterface::class);

        $this->transition = new ContinueToShippingMethod(
            $this->addressActions,
            $this->shippingMethodActions,
            $this->baseContinueTransition
        );
    }

    private function getWorkflowItem(Checkout $checkout): WorkflowItem
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects(self::any())
            ->method('getEntity')
            ->willReturn($checkout);

        return $workflowItem;
    }

    public function testIsPreConditionAllowedWithValidConditions(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $errors = new ArrayCollection();

        $this->baseContinueTransition->expects(self::once())
            ->method('isPreConditionAllowed')
            ->with($workflowItem, $errors)
            ->willReturn(true);

        self::assertTrue($this->transition->isPreConditionAllowed($workflowItem, $errors));
    }

    public function testIsPreConditionAllowedWithInvalidConditions(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);

        $this->baseContinueTransition->expects(self::once())
            ->method('isPreConditionAllowed')
            ->with($workflowItem, null)
            ->willReturn(false);

        self::assertFalse($this->transition->isPreConditionAllowed($workflowItem));
    }

    public function testIsConditionAllowedWithShipToBillingAddress(): void
    {
        $checkout = new Checkout();
        $checkout->setShipToBillingAddress(true);
        $workflowItem = $this->getWorkflowItem($checkout);

        self::assertTrue($this->transition->isConditionAllowed($workflowItem));
    }

    public function testIsConditionAllowedWithShippingAddress(): void
    {
        $checkout = new Checkout();
        $checkout->setShippingAddress(new OrderAddress());
        $workflowItem = $this->getWorkflowItem($checkout);

        self::assertTrue($this->transition->isConditionAllowed($workflowItem));
    }

    public function testIsConditionAllowedWithoutShippingAddress(): void
    {
        $checkout = new Checkout(); // No Shipping Address set
        $workflowItem = $this->getWorkflowItem($checkout);

        self::assertFalse($this->transition->isConditionAllowed($workflowItem));
    }

    public function testExecute(): void
    {
        $checkout = new Checkout();
        $workflowItem = $this->getWorkflowItem($checkout);

        $this->addressActions->expects(self::once())
            ->method('updateShippingAddress')
            ->with($checkout);

        $this->shippingMethodActions->expects(self::once())
            ->method('updateDefaultShippingMethods')
            ->with($checkout, null, null, false);

        $this->transition->execute($workflowItem);
    }

    public function testExecuteUpdateEmail(): void
    {
        $address = new OrderAddress();
        $address->setCustomerUserAddress(new CustomerUserAddress());
        $checkout = new Checkout();
        $checkout->setBillingAddress($address);
        $checkout->setCustomerUser((new CustomerUser())->setEmail('test@test.com'));
        $workflowItem = new WorkflowItem();
        $workflowItem->setEntity($checkout);

        $this->addressActions->expects(self::once())
            ->method('updateShippingAddress')
            ->with($checkout);

        $this->shippingMethodActions->expects(self::once())
            ->method('updateDefaultShippingMethods')
            ->with($checkout, null, null, false);

        $this->transition->execute($workflowItem);

        self::assertEquals('test@test.com', $workflowItem->getData()->get('email'));
    }
}
