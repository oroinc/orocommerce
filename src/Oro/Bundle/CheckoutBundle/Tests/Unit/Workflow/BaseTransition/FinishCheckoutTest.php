<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\BaseTransition;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CheckoutActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CustomerUserActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\BaseTransition\FinishCheckout;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FinishCheckoutTest extends TestCase
{
    private CustomerUserActionsInterface|MockObject $customerUserActions;
    private CheckoutActionsInterface|MockObject $checkoutActions;

    private FinishCheckout $finishCheckout;

    #[\Override]
    protected function setUp(): void
    {
        $this->customerUserActions = $this->createMock(CustomerUserActionsInterface::class);
        $this->checkoutActions = $this->createMock(CheckoutActionsInterface::class);

        $this->finishCheckout = new FinishCheckout(
            $this->customerUserActions,
            $this->checkoutActions
        );
    }

    public function testIsConditionAllowedReturnsFalseIfNoOrder(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = new Checkout();

        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $this->assertFalse($this->finishCheckout->isConditionAllowed($workflowItem));
    }

    public function testIsConditionAllowedReturnsFalseIfNoPaymentInProgress(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $order = new Order();
        $checkout = new Checkout();
        $checkout->setOrder($order);

        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $this->assertFalse($this->finishCheckout->isConditionAllowed($workflowItem));
    }

    public function testIsConditionAllowedReturnsTrue(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $order = new Order();
        $checkout = new Checkout();
        $checkout->setOrder($order);
        $checkout->setPaymentInProgress(true);

        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $this->assertTrue($this->finishCheckout->isConditionAllowed($workflowItem));
    }

    public function testExecute(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = new Checkout();
        $checkout->setPaymentInProgress(true);
        $order = new Order();
        $checkout->setOrder($order);
        $data = new WorkflowData([
            'late_registration' => ['email' => 'test@test.com'],
            'auto_remove_source' => true,
            'allow_manual_source_remove' => false,
            'remove_source' => true,
            'clear_source' => false,
        ]);

        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $workflowItem->expects($this->once())
            ->method('getData')
            ->willReturn($data);

        $this->customerUserActions->expects($this->once())
            ->method('handleLateRegistration')
            ->with(
                $checkout,
                $order,
                ['email' => 'test@test.com']
            );

        $this->checkoutActions->expects($this->once())
            ->method('finishCheckout')
            ->with(
                $checkout,
                $order,
                true,
                false,
                true,
                false
            );

        $this->finishCheckout->execute($workflowItem);

        $this->assertFalse($checkout->isPaymentInProgress());
    }
}
