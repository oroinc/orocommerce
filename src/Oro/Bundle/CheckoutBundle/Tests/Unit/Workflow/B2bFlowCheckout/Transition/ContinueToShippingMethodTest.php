<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Action\DefaultShippingMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Action\MultiShipping\DefaultMultiShippingGroupMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Action\MultiShipping\DefaultMultiShippingMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\AddressActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition\ContinueToShippingMethod;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContinueToShippingMethodTest extends TestCase
{
    private AddressActionsInterface|MockObject $addressActions;
    private ConfigProvider|MockObject $configProvider;
    private DefaultShippingMethodSetterInterface|MockObject $defaultShippingMethodSetter;
    private DefaultMultiShippingMethodSetterInterface|MockObject $defaultMultiShippingMethodSetter;
    private DefaultMultiShippingGroupMethodSetterInterface|MockObject $defaultMultiShippingGroupMethodSetter;
    private TransitionServiceInterface|MockObject $baseContinueTransition;

    private ContinueToShippingMethod $transition;

    #[\Override]
    protected function setUp(): void
    {
        $this->addressActions = $this->createMock(AddressActionsInterface::class);
        $this->configProvider = $this->createMock(ConfigProvider::class);
        $this->defaultShippingMethodSetter = $this->createMock(DefaultShippingMethodSetterInterface::class);
        $this->defaultMultiShippingMethodSetter = $this->createMock(DefaultMultiShippingMethodSetterInterface::class);
        $this->defaultMultiShippingGroupMethodSetter = $this->createMock(
            DefaultMultiShippingGroupMethodSetterInterface::class
        );
        $this->baseContinueTransition = $this->createMock(TransitionServiceInterface::class);

        $this->transition = new ContinueToShippingMethod(
            $this->addressActions,
            $this->configProvider,
            $this->defaultShippingMethodSetter,
            $this->defaultMultiShippingMethodSetter,
            $this->defaultMultiShippingGroupMethodSetter,
            $this->baseContinueTransition
        );
    }

    public function testIsPreConditionAllowedWithValidConditions()
    {
        $errors = new ArrayCollection();
        $workflowItem = $this->createMock(WorkflowItem::class);

        $this->baseContinueTransition->expects($this->once())
            ->method('isPreConditionAllowed')
            ->with($workflowItem, $errors)
            ->willReturn(true);

        $result = $this->transition->isPreConditionAllowed($workflowItem, $errors);

        $this->assertTrue($result);
    }

    public function testIsPreConditionAllowedWithInvalidConditions()
    {
        $workflowItem = $this->createMock(WorkflowItem::class);

        $this->baseContinueTransition->expects($this->once())
            ->method('isPreConditionAllowed')
            ->with($workflowItem, null)
            ->willReturn(false);

        $result = $this->transition->isPreConditionAllowed($workflowItem);

        $this->assertFalse($result);
    }

    public function testIsConditionAllowedWithShipToBillingAddress()
    {
        $checkout = new Checkout();
        $checkout->setShipToBillingAddress(true);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);

        $result = $this->transition->isConditionAllowed($workflowItem);

        $this->assertTrue($result);
    }

    public function testIsConditionAllowedWithShippingAddress()
    {
        $checkout = new Checkout();
        $shippingAddress = new OrderAddress();
        $checkout->setShippingAddress($shippingAddress);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);

        $result = $this->transition->isConditionAllowed($workflowItem);

        $this->assertTrue($result);
    }

    public function testIsConditionAllowedWithoutShippingAddress()
    {
        $checkout = new Checkout(); // No Shipping Address set

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);

        $result = $this->transition->isConditionAllowed($workflowItem);

        $this->assertFalse($result);
    }

    public function testExecuteWithMultiShippingDisabled()
    {
        $checkout = new Checkout();
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);

        $this->addressActions->expects($this->once())
            ->method('updateShippingAddress')
            ->with($checkout);

        $this->configProvider->expects($this->once())
            ->method('isMultiShippingEnabled')
            ->willReturn(false);

        $this->defaultShippingMethodSetter->expects($this->once())
            ->method('setDefaultShippingMethod')
            ->with($checkout);

        $this->defaultMultiShippingMethodSetter->expects($this->never())
            ->method('setDefaultShippingMethods');

        $this->defaultMultiShippingGroupMethodSetter->expects($this->never())
            ->method('setDefaultShippingMethods');

        $this->transition->execute($workflowItem);
    }

    public function testExecuteWithShippingSelectionByLineItemEnabled()
    {
        $checkout = new Checkout();
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);

        $this->addressActions->expects($this->once())
            ->method('updateShippingAddress')
            ->with($checkout);

        $this->configProvider->expects($this->once())
            ->method('isMultiShippingEnabled')
            ->willReturn(true);

        $this->configProvider->expects($this->once())
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(true);

        $this->defaultShippingMethodSetter->expects($this->never())
            ->method('setDefaultShippingMethod');

        $this->defaultMultiShippingMethodSetter->expects($this->once())
            ->method('setDefaultShippingMethods')
            ->with($checkout);

        $this->defaultMultiShippingGroupMethodSetter->expects($this->never())
            ->method('setDefaultShippingMethods');

        $this->transition->execute($workflowItem);
    }

    public function testExecuteWithLineItemsGroupingEnabled()
    {
        $checkout = new Checkout();
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);

        $this->addressActions->expects($this->once())
            ->method('updateShippingAddress')
            ->with($checkout);

        $this->configProvider->expects($this->once())
            ->method('isMultiShippingEnabled')
            ->willReturn(true);

        $this->configProvider->expects($this->exactly(2))
            ->method('isShippingSelectionByLineItemEnabled')
            ->willReturn(false);

        $this->configProvider->expects($this->once())
            ->method('isLineItemsGroupingEnabled')
            ->willReturn(true);

        $this->defaultShippingMethodSetter->expects($this->never())
            ->method('setDefaultShippingMethod');

        $this->defaultMultiShippingMethodSetter->expects($this->never())
            ->method('setDefaultShippingMethods');

        $this->defaultMultiShippingGroupMethodSetter->expects($this->once())
            ->method('setDefaultShippingMethods')
            ->with($checkout);

        $this->transition->execute($workflowItem);
    }
}
