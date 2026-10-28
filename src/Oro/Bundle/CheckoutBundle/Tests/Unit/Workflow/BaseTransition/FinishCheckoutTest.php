<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\BaseTransition;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CheckoutActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\CustomerUserActionsInterface;
use Oro\Bundle\CheckoutBundle\Workflow\BaseTransition\FinishCheckout;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FinishCheckoutTest extends TestCase
{
    private CustomerUserActionsInterface|MockObject $customerUserActions;
    private CheckoutActionsInterface|MockObject $checkoutActions;
    private PaymentTransactionProvider|MockObject $paymentTransactionProvider;

    private FinishCheckout $finishCheckout;

    #[\Override]
    protected function setUp(): void
    {
        $this->customerUserActions = $this->createMock(CustomerUserActionsInterface::class);
        $this->checkoutActions = $this->createMock(CheckoutActionsInterface::class);
        $this->paymentTransactionProvider = $this->createMock(PaymentTransactionProvider::class);

        $this->finishCheckout = new FinishCheckout(
            $this->customerUserActions,
            $this->checkoutActions,
            $this->paymentTransactionProvider
        );
    }

    public function testIsConditionAllowedReturnsFalseIfNoOrder(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $checkout = new Checkout();

        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $this->paymentTransactionProvider->expects($this->never())
            ->method('getPaymentTransaction');

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

        $this->paymentTransactionProvider->expects($this->never())
            ->method('getPaymentTransaction');

        $this->assertFalse($this->finishCheckout->isConditionAllowed($workflowItem));
    }

    public function testIsConditionAllowedReturnsFalseIfNoSuccessfulPaymentTransaction(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $order = new Order();
        $checkout = new Checkout();
        $checkout->setOrder($order);
        $checkout->setPaymentInProgress(true);

        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $this->paymentTransactionProvider->expects($this->once())
            ->method('getPaymentTransaction')
            ->with($order)
            ->willReturn(null);

        $this->assertFalse($this->finishCheckout->isConditionAllowed($workflowItem));
    }

    public function testIsConditionAllowedReturnsFalseWhenLastPaymentTransactionIsDeclined(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $order = new Order();
        $checkout = new Checkout();
        $checkout->setOrder($order);
        $checkout->setPaymentInProgress(true);

        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setSuccessful(false);
        $paymentTransaction->setActive(false);

        $this->paymentTransactionProvider->expects($this->once())
            ->method('getPaymentTransaction')
            ->with($order)
            ->willReturn($paymentTransaction);

        $this->assertFalse($this->finishCheckout->isConditionAllowed($workflowItem));
    }

    public function testIsConditionAllowedReturnsTrueWhenLastPaymentTransactionIsActiveButNotSuccessful(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $order = new Order();
        $checkout = new Checkout();
        $checkout->setOrder($order);
        $checkout->setPaymentInProgress(true);

        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        // e.g. a reservation that is activated later or a payment that awaits a confirmation
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setSuccessful(false);
        $paymentTransaction->setActive(true);

        $this->paymentTransactionProvider->expects($this->once())
            ->method('getPaymentTransaction')
            ->with($order)
            ->willReturn($paymentTransaction);

        $this->assertTrue($this->finishCheckout->isConditionAllowed($workflowItem));
    }

    public function testIsConditionAllowedQueriesTransactionsForOrderNotCheckout(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $order = new Order();
        $checkout = new Checkout();
        $checkout->setOrder($order);
        $checkout->setPaymentInProgress(true);

        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setSuccessful(true);

        $this->paymentTransactionProvider->expects($this->once())
            ->method('getPaymentTransaction')
            ->with($this->identicalTo($order))
            ->willReturn($paymentTransaction);

        $this->assertTrue($this->finishCheckout->isConditionAllowed($workflowItem));
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

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setSuccessful(true);

        $this->paymentTransactionProvider->expects($this->once())
            ->method('getPaymentTransaction')
            ->with($order)
            ->willReturn($paymentTransaction);

        $this->assertTrue($this->finishCheckout->isConditionAllowed($workflowItem));
    }

    public function testIsConditionAllowedWithRetriedPaymentUsesLatestTransaction(): void
    {
        $workflowItem = $this->createMock(WorkflowItem::class);
        $order = new Order();
        $checkout = new Checkout();
        $checkout->setOrder($order);
        $checkout->setPaymentInProgress(true);

        $workflowItem->expects($this->once())
            ->method('getEntity')
            ->willReturn($checkout);

        // the provider is responsible for returning the latest transaction (e.g. after an earlier
        // failed attempt was retried); the gate only needs to trust its successful flag
        $latestPaymentTransaction = new PaymentTransaction();
        $latestPaymentTransaction->setSuccessful(true);

        $this->paymentTransactionProvider->expects($this->once())
            ->method('getPaymentTransaction')
            ->with($order)
            ->willReturn($latestPaymentTransaction);

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
