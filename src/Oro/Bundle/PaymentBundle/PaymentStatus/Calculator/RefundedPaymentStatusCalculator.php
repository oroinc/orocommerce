<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\PaymentStatus\Calculator;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\Context\PaymentStatusCalculationContext;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;

/**
 * Calculates the payment status for an entity when the payment is refunded.
 *
 * This class must be used only to calculate the payment status for an entity.
 * In order just to get the current payment status - use {@see PaymentStatusManager}.
 */
class RefundedPaymentStatusCalculator implements PaymentStatusCalculatorInterface
{
    public function __construct(
        private readonly PaymentStatusCalculationHelper $paymentStatusCalculationHelper
    ) {
    }

    #[\Override]
    public function calculatePaymentStatus(
        object $entity,
        ?PaymentStatusCalculationContext $paymentStatusCalculationContext = null
    ): ?string {
        $total = $paymentStatusCalculationContext?->get('total');
        if (!$total instanceof Subtotal) {
            return null;
        }

        $paymentTransactions = $paymentStatusCalculationContext?->get('paymentTransactions');
        if (!is_iterable($paymentTransactions)) {
            return null;
        }

        $refundTransactions = $this->getRefundedTransactions($paymentTransactions);
        $totalAmount = $total->getAmount();
        if ($this->paymentStatusCalculationHelper->isTransactionsAmountLessThan($refundTransactions, $totalAmount)) {
            return PaymentStatuses::REFUNDED_PARTIALLY;
        }

        if (count($refundTransactions)) {
            return PaymentStatuses::REFUNDED;
        }

        return null;
    }

    private function getRefundedTransactions(iterable $paymentTransactions): array
    {
        $refundTransactions = [];
        foreach ($paymentTransactions as $paymentTransaction) {
            if (!$paymentTransaction instanceof PaymentTransaction) {
                continue;
            }

            if ($paymentTransaction->isClone()) {
                continue;
            }

            if (
                $paymentTransaction->isSuccessful() &&
                $paymentTransaction->getAction() === PaymentMethodInterface::REFUND
            ) {
                $refundTransactions[] = $paymentTransaction;
            }
        }

        return $refundTransactions;
    }
}
