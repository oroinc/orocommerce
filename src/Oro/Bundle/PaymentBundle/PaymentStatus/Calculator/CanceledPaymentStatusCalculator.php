<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\PaymentStatus\Calculator;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\Context\PaymentStatusCalculationContext;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;

/**
 * Calculates the payment status for an entity when the payment is canceled.
 *
 * This class must be used only to calculate the payment status for an entity.
 * In order just to get the current payment status - use {@see PaymentStatusManager}.
 */
class CanceledPaymentStatusCalculator implements PaymentStatusCalculatorInterface
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

        $authorizeTransactions = $this->getAuthorizeTransactions($paymentTransactions);
        $canceledTransactions = $this->getCanceledTransactions($paymentTransactions);
        if (!$canceledTransactions) {
            return null;
        }

        $authorizedAmount = $this->paymentStatusCalculationHelper->sumTransactionAmounts($authorizeTransactions);
        $canceledAmount = $this->paymentStatusCalculationHelper->sumTransactionAmounts($canceledTransactions);

        if ($canceledAmount->isGreaterThanOrEqualTo($authorizedAmount)) {
            return PaymentStatuses::CANCELED;
        }

        return null;
    }

    private function getAuthorizeTransactions(iterable $paymentTransactions): array
    {
        $filteredTransactions = [];
        foreach ($paymentTransactions as $paymentTransaction) {
            if (!$paymentTransaction instanceof PaymentTransaction) {
                continue;
            }

            if ($paymentTransaction->isClone()) {
                continue;
            }

            if (!$paymentTransaction->isSuccessful() || !$paymentTransaction->isActive()) {
                continue;
            }

            if ($paymentTransaction->getAction() === PaymentMethodInterface::AUTHORIZE) {
                $filteredTransactions[] = $paymentTransaction;
            }
        }

        return $filteredTransactions;
    }

    private function getCanceledTransactions(iterable $paymentTransactions): array
    {
        $filteredTransactions = [];
        foreach ($paymentTransactions as $paymentTransaction) {
            if (!$paymentTransaction instanceof PaymentTransaction) {
                continue;
            }

            if ($paymentTransaction->isClone()) {
                continue;
            }

            if (
                $paymentTransaction->isSuccessful() &&
                $paymentTransaction->getAction() === PaymentMethodInterface::CANCEL
            ) {
                $filteredTransactions[] = $paymentTransaction;
            }
        }

        return $filteredTransactions;
    }
}
