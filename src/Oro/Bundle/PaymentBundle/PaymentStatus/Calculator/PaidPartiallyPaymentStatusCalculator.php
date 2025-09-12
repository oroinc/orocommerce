<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\PaymentStatus\Calculator;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\Context\PaymentStatusCalculationContext;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;

/**
 * Calculates the payment status for an entity when the payment amount is paid partially.
 *
 * This class must be used only to calculate the payment status for an entity.
 * In order just to get the current payment status - use {@see PaymentStatusManager}.
 */
class PaidPartiallyPaymentStatusCalculator implements PaymentStatusCalculatorInterface
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

        $successfulTransactions = $this->getSuccessfulPaymentTransactions($paymentTransactions);

        if (!count($successfulTransactions)) {
            return null;
        }

        $totalAmount = $total->getAmount();
        $isPaidPartially = $this->paymentStatusCalculationHelper
            ->isTransactionsAmountLessThan($successfulTransactions, $totalAmount);

        if ($isPaidPartially) {
            return PaymentStatuses::PAID_PARTIALLY;
        }

        return null;
    }

    private function getSuccessfulPaymentTransactions(ArrayCollection $paymentTransactions): array
    {
        $filteredTransactions = [];
        foreach ($paymentTransactions as $paymentTransaction) {
            if (!$paymentTransaction instanceof PaymentTransaction) {
                continue;
            }

            if (!$paymentTransaction->isSuccessful()) {
                continue;
            }

            if (in_array(
                $paymentTransaction->getAction(),
                [
                    PaymentMethodInterface::CAPTURE,
                    PaymentMethodInterface::CHARGE,
                    PaymentMethodInterface::PURCHASE,
                ],
                true
            )) {
                $filteredTransactions[] = $paymentTransaction;
            }
        }

        return $filteredTransactions;
    }
}
