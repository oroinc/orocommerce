<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckout\Transition;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\Transition\ClearPaymentMethodAndRecalculateState;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use PHPUnit\Framework\TestCase;

class ClearPaymentMethodAndRecalculateStateTest extends TestCase
{
    private ClearPaymentMethodAndRecalculateState $transition;

    protected function setUp(): void
    {
        $this->transition = new ClearPaymentMethodAndRecalculateState();
    }

    public function testIsPreConditionAllowedWhenCheckoutNotCompleted()
    {
        $checkout = new Checkout();
        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);

        $result = $this->transition->isPreConditionAllowed($workflowItem);
        $this->assertTrue($result);
    }

    public function testIsPreConditionAllowedWhenCheckoutCompleted()
    {
        $checkout = new Checkout();
        $checkout->setCompleted(true);

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);

        $result = $this->transition->isPreConditionAllowed($workflowItem);
        $this->assertFalse($result);
    }

    public function testExecute()
    {
        $checkout = new Checkout();
        $checkout->setShippingCost(Price::create(100, 'USD'));

        $workflowData = new WorkflowData();
        $workflowData['payment_method'] = 'some_payment_method';
        $workflowData['shipping_method'] = 'some_shipping_method';
        $workflowData['payment_in_progress'] = true;
        $workflowData['shipping_data_ready'] = true;

        $workflowItem = $this->createMock(WorkflowItem::class);
        $workflowItem->method('getEntity')->willReturn($checkout);
        $workflowItem->method('getData')->willReturn($workflowData);

        $this->transition->execute($workflowItem);

        $this->assertNull($checkout->getShippingCost());
        $this->assertNull($workflowData['payment_method']);
        $this->assertNull($workflowData['shipping_method']);
        $this->assertFalse($workflowData['payment_in_progress']);
        $this->assertFalse($workflowData['shipping_data_ready']);
    }
}
