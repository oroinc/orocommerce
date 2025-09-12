<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\PaymentStatus\Calculator;

use Oro\Bundle\PaymentBundle\PaymentStatus\Context\PaymentStatusCalculationContext;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;

/**
 * Calculates the payment status for an entity when the payment is declined.
 *
 * This class must be used only to calculate the payment status for an entity.
 * In order just to get the current payment status - use {@see PaymentStatusManager}.
 */
class DeclinedPaymentStatusCalculator implements PaymentStatusCalculatorInterface
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

        $declinedCount = 0;
        $totalCount = 0;
        foreach ($paymentTransactions as $paymentTransaction) {
            $totalCount++;
            if (!$paymentTransaction->isSuccessful() && !$paymentTransaction->isActive()) {
                $declinedCount++;
            }
        }

        if (!$totalCount) {
            return null;
        }

        if ($declinedCount === $totalCount) {
            return PaymentStatuses::DECLINED;
        }

        return null;
    }
}
