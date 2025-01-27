<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckout\Transition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Action\DefaultPaymentMethodSetterInterface;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\AvailableShippingMethodCheckerInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\ShippingMethodActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition\ContinueToPayment;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ContinueToPaymentTest extends TestCase
{
    private ShippingMethodActionsInterface|MockObject $shippingMethodActions;
    private AvailableShippingMethodCheckerInterface|MockObject $availableShippingMethodChecker;
    private DefaultPaymentMethodSetterInterface|MockObject $defaultPaymentMethodSetter;
    private TransitionServiceInterface|MockObject $baseContinueTransition;

    private ContinueToPayment $transition;

    #[\Override]
    protected function setUp(): void
    {
        $this->shippingMethodActions = $this->createMock(ShippingMethodActionsInterface::class);
        $this->availableShippingMethodChecker = $this->createMock(AvailableShippingMethodCheckerInterface::class);
        $this->defaultPaymentMethodSetter = $this->createMock(DefaultPaymentMethodSetterInterface::class);
        $this->baseContinueTransition = $this->createMock(TransitionServiceInterface::class);

        $this->transition = new ContinueToPayment(
            $this->shippingMethodActions,
            $this->availableShippingMethodChecker,
            $this->defaultPaymentMethodSetter,
            $this->baseContinueTransition
        );
    }

    public function testIsPreConditionAllowedWithNotAllowedByBasicCheck()
    {
        $errors = new ArrayCollection();
        $workflowItem = $this->createMock(WorkflowItem::class);

        $this->baseContinueTransition->expects($this->once())
            ->method('isPreConditionAllowed')
            ->with($workflowItem, $errors)
            ->willReturn(false);

        $this->shippingMethodActions->expects($this->never())
            ->method('updateDefaultShippingMethods');

        $this->availableShippingMethodChecker->expects($this->never())
            ->method('hasAvailableShippingMethods');

        $result = $this->transition->isPreConditionAllowed($workflowItem, $errors);

        $this->assertFalse($result);
    }

    public function testIsPreConditionAllowedWithValidConditions()
    {
        $errors = new ArrayCollection();
        $lineItemsShippingMethods = ['flat_rate'];
        $lineItemGroupsShippingMethods = ['group1' => ['flat_rate2']];

        $checkout = new Checkout();
        $workflowData = new WorkflowData();
        $workflowData->offsetSet('line_items_shipping_methods', $lineItemsShippingMethods);
        $workflowData->offsetSet('line_item_groups_shipping_methods', $lineItemGroupsShippingMethods);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);
        $workflowItem->method('getData')->willReturn($workflowData);

        $this->baseContinueTransition->expects($this->once())
            ->method('isPreConditionAllowed')
            ->with($workflowItem, $errors)
            ->willReturn(true);

        $this->shippingMethodActions->expects($this->once())
            ->method('updateDefaultShippingMethods')
            ->with($checkout, $lineItemsShippingMethods, $lineItemGroupsShippingMethods);

        $this->availableShippingMethodChecker->expects($this->once())
            ->method('hasAvailableShippingMethods')
            ->with($checkout)
            ->willReturn(true);

        $result = $this->transition->isPreConditionAllowed($workflowItem, $errors);

        $this->assertTrue($result);
    }

    public function testIsPreConditionAllowedWithNoAvailableShippingMethods()
    {
        $errors = new ArrayCollection();
        $lineItemsShippingMethods = ['flat_rate'];
        $lineItemGroupsShippingMethods = ['group1' => ['flat_rate2']];

        $checkout = new Checkout();
        $workflowData = new WorkflowData();
        $workflowData->offsetSet('line_items_shipping_methods', $lineItemsShippingMethods);
        $workflowData->offsetSet('line_item_groups_shipping_methods', $lineItemGroupsShippingMethods);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);
        $workflowItem->method('getData')->willReturn($workflowData);

        $this->baseContinueTransition->expects($this->once())
            ->method('isPreConditionAllowed')
            ->with($workflowItem, $errors)
            ->willReturn(true);

        $this->shippingMethodActions->expects($this->once())
            ->method('updateDefaultShippingMethods')
            ->with($checkout, $lineItemsShippingMethods, $lineItemGroupsShippingMethods);

        $this->availableShippingMethodChecker->expects($this->once())
            ->method('hasAvailableShippingMethods')
            ->with($checkout)
            ->willReturn(false);

        $result = $this->transition->isPreConditionAllowed($workflowItem, $errors);

        $this->assertFalse($result);
        $this->assertEqualsCanonicalizing(
            [['message' => 'oro.checkout.validator.has_applicable_shipping_rules.message']],
            $errors->toArray()
        );
    }

    public function testIsConditionAllowedWithValidConditions()
    {
        $checkout = new Checkout();
        $checkout->setShippingMethod('flat_rate');
        $workflowData = new WorkflowData();

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);
        $workflowItem->method('getData')->willReturn($workflowData);

        $this->shippingMethodActions->expects($this->once())
            ->method('hasApplicableShippingRules')
            ->with($checkout, null)
            ->willReturn(true);

        $result = $this->transition->isConditionAllowed($workflowItem);

        $this->assertTrue($result);
    }

    public function testIsConditionAllowedWithNoShippingMethod()
    {
        $checkout = new Checkout();
        $workflowData = new WorkflowData();

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);
        $workflowItem->method('getData')->willReturn($workflowData);

        $this->shippingMethodActions->expects($this->never())
            ->method('hasApplicableShippingRules');

        $errors = new ArrayCollection();
        $result = $this->transition->isConditionAllowed($workflowItem, $errors);

        $this->assertFalse($result);
        $this->assertNotEmpty($errors);
        $this->assertEqualsCanonicalizing(
            [['message' => 'oro.checkout.validator.has_applicable_shipping_rules.message']],
            $errors->toArray()
        );
    }

    public function testIsConditionAllowedWithNoApplicableShippingRules()
    {
        $errors = new ArrayCollection();

        $checkout = new Checkout();
        $checkout->setShippingMethod('flat_rate');
        $workflowData = new WorkflowData();

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);
        $workflowItem->method('getData')->willReturn($workflowData);

        $this->shippingMethodActions->expects($this->once())
            ->method('hasApplicableShippingRules')
            ->with($checkout, $errors)
            ->willReturn(false);

        $result = $this->transition->isConditionAllowed($workflowItem, $errors);

        $this->assertFalse($result);
    }

    public function testExecute()
    {
        $checkout = new Checkout();
        $workflowData = new WorkflowData();

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);
        $workflowItem->expects($this->once())
            ->method('getData')
            ->willReturn($workflowData);

        $this->shippingMethodActions->expects($this->once())
            ->method('updateCheckoutShippingPrices')
            ->with($checkout);

        $this->defaultPaymentMethodSetter->expects($this->once())
            ->method('setDefaultPaymentMethod')
            ->with($checkout);

        $this->transition->execute($workflowItem);

        $this->assertTrue($workflowData->offsetGet('shipping_data_ready'));
    }
}
