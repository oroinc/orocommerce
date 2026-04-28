<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\BaseTransition;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\PromotionBundle\Manager\CouponUsageManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceAbstract;
use Oro\Bundle\WorkflowBundle\Model\TransitionServiceInterface;

/**
 * Base implementation of checkout payment_error transition.
 */
class PaymentError extends TransitionServiceAbstract
{
    private ?PaymentStatusManager $paymentStatusManager = null;

    private ?CouponUsageManager $couponUsageManager = null;

    public function __construct(
        private readonly TransitionServiceInterface $baseTransition,
        private readonly ManagerRegistry $doctrine
    ) {
    }

    public function setPaymentStatusManager(PaymentStatusManager $paymentStatusManager): void
    {
        $this->paymentStatusManager = $paymentStatusManager;
    }

    #[\Override]
    public function execute(WorkflowItem $workflowItem): void
    {
        $this->baseTransition->execute($workflowItem);

        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        $checkout->setPaymentInProgress(false);
        $order = $checkout->getOrder();
        if (null !== $order && !$this->isOrderPaid($order)) {
            $this->couponUsageManager->revertCouponUsages($order->getAppliedCoupons(), $order->getCustomerUser());
            $checkout->setOrder(null);
            $this->doctrine->getManagerForClass(Order::class)->remove($order);
        }
    }

    private function isOrderPaid(Order $order): bool
    {
        $paymentStatus = (string) $this->paymentStatusManager->getPaymentStatus($order);

        return in_array($paymentStatus, [
            PaymentStatuses::PAID_IN_FULL,
            PaymentStatuses::PAID_PARTIALLY,
            PaymentStatuses::AUTHORIZED,
            PaymentStatuses::AUTHORIZED_PARTIALLY,
        ], true);
    }

    public function setCouponUsageManager(CouponUsageManager $couponUsageManager): void
    {
        $this->couponUsageManager = $couponUsageManager;
    }
}
