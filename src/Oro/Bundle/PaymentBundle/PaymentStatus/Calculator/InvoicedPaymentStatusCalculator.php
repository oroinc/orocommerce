<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\PaymentStatus\Calculator;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\Context\PaymentStatusCalculationContext;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;

/**
 * Calculates the payment status for an entity when the payment is invoiced.
 *
 * This class must be used only to calculate the payment status for an entity.
 * In order just to get the current payment status - use {@see PaymentStatusManager}.
 */
class InvoicedPaymentStatusCalculator implements PaymentStatusCalculatorInterface
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
        $paymentTransactions = $paymentStatusCalculationContext?->get('paymentTransactions');
        if (!is_iterable($paymentTransactions)) {
            return null;
        }

        if ($this->hasInvoiceTransactions($paymentTransactions)) {
            return PaymentStatuses::INVOICED;
        }

        return null;
    }

    private function hasInvoiceTransactions(iterable $paymentTransactions): bool
    {
        foreach ($paymentTransactions as $paymentTransaction) {
            if (!$paymentTransaction->isClone()
                && $paymentTransaction->isActive()
                && $paymentTransaction->isSuccessful()
                && $paymentTransaction->getAction() === PaymentMethodInterface::INVOICE) {
                return true;
            }
        }

        return false;
    }
}
