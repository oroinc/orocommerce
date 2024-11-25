<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\AddressActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CustomerUserActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition\ContinueToShippingAddress;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContinueToShippingAddressTest extends TestCase
{
    private ActionExecutor|MockObject $actionExecutor;
    private CustomerUserActionsInterface|MockObject $customerUserActions;
    private AddressActionsInterface|MockObject $addressActions;
    private TransitionServiceInterface|MockObject $baseContinueTransition;
    private WorkflowManager|MockObject $workflowManager;

    private ContinueToShippingAddress $transition;

    #[\Override]
    protected function setUp(): void
    {
        $this->actionExecutor = $this->createMock(ActionExecutor::class);
        $this->customerUserActions = $this->createMock(CustomerUserActionsInterface::class);
        $this->addressActions = $this->createMock(AddressActionsInterface::class);
        $this->baseContinueTransition = $this->createMock(TransitionServiceInterface::class);
        $this->workflowManager = $this->createMock(WorkflowManager::class);

        $this->transition = new ContinueToShippingAddress(
            $this->actionExecutor,
            $this->customerUserActions,
            $this->addressActions,
            $this->baseContinueTransition,
            $this->workflowManager
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
        $errors = new ArrayCollection();
        $workflowItem = $this->createMock(WorkflowItem::class);

        $this->baseContinueTransition->expects($this->once())
            ->method('isPreConditionAllowed')
            ->with($workflowItem, $errors)
            ->willReturn(false);

        $result = $this->transition->isPreConditionAllowed($workflowItem, $errors);

        $this->assertFalse($result);
    }

    public function testIsConditionAllowedWithBillingAddress()
    {
        $checkout = new Checkout();
        $billingAddress = new OrderAddress();
        $checkout->setBillingAddress($billingAddress);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);

        $result = $this->transition->isConditionAllowed($workflowItem);

        $this->assertTrue($result);
    }

    public function testIsConditionAllowedWithoutBillingAddress()
    {
        $checkout = new Checkout();

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);

        $result = $this->transition->isConditionAllowed($workflowItem);

        $this->assertFalse($result);
    }

    public function testExecute()
    {
        $checkout = new Checkout();
        $billingAddress = new OrderAddress();
        $checkout->setBillingAddress($billingAddress);

        $workflowData = new WorkflowData();
        $workflowData->offsetSet('email', 'test@example.com');
        $workflowData->offsetSet('disallow_shipping_address_edit', false);
        $workflowData->offsetSet('customerConsents', ['consent']);
        $workflowData->offsetSet('ship_to_billing_address', true);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);
        $workflowItem->method('getData')->willReturn($workflowData);

        $this->customerUserActions->expects($this->once())
            ->method('updateGuestCustomerUser')
            ->with($checkout, 'test@example.com', $billingAddress);

        $this->customerUserActions->expects($this->once())
            ->method('createGuestCustomerUser')
            ->with($checkout, 'test@example.com', $billingAddress);

        $this->addressActions->expects($this->once())
            ->method('updateBillingAddress')
            ->with($checkout, false)
            ->willReturn(true);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with('save_accepted_consents', ['acceptedConsents' => ['consent']]);

        $this->workflowManager->expects($this->once())
            ->method('transit')
            ->with($workflowItem, 'continue_to_shipping_method');

        $this->transition->execute($workflowItem);

        $this->assertTrue($workflowData->offsetGet('billing_address_has_shipping'));
        $this->assertNull($workflowData->offsetGet('customerConsents'));
    }

    public function testExecuteWithoutShipToBillingAddress()
    {
        $checkout = new Checkout();
        $billingAddress = new OrderAddress();
        $checkout->setBillingAddress($billingAddress);

        $workflowData = new WorkflowData();
        $workflowData->offsetSet('email', 'test@example.com');
        $workflowData->offsetSet('disallow_shipping_address_edit', false);
        $workflowData->offsetSet('customerConsents', ['consent']);
        $workflowData->offsetSet('ship_to_billing_address', false);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);
        $workflowItem->method('getData')->willReturn($workflowData);

        $this->customerUserActions->expects($this->once())
            ->method('updateGuestCustomerUser')
            ->with($checkout, 'test@example.com', $billingAddress);

        $this->customerUserActions->expects($this->once())
            ->method('createGuestCustomerUser')
            ->with($checkout, 'test@example.com', $billingAddress);

        $this->addressActions->expects($this->once())
            ->method('updateBillingAddress')
            ->with($checkout, false)
            ->willReturn(true);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with('save_accepted_consents', ['acceptedConsents' => ['consent']]);

        $this->workflowManager->expects($this->never())
            ->method('transit');

        $this->transition->execute($workflowItem);

        $this->assertTrue($workflowData->offsetGet('billing_address_has_shipping'));
        $this->assertNull($workflowData->offsetGet('customerConsents'));
    }
}
