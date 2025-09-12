<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\PaymentStatus\Calculator;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Component\Math\BigDecimal;

/**
 * Contains handy methods for payment status calculations.
 */
class PaymentStatusCalculationHelper
{
    /**
     * @param iterable<PaymentTransaction> $paymentTransactions
     *
     * @return BigDecimal
     */
    public function sumTransactionAmounts(iterable $paymentTransactions): BigDecimal
    {
        $sum = BigDecimal::of(0);
        foreach ($paymentTransactions as $paymentTransaction) {
            $sum = $sum->plus($paymentTransaction->getAmount());
        }

        return $sum;
    }

    /**
     * @param iterable<PaymentTransaction> $paymentTransactions
     * @param float $amount Amount to compare with the sum of transactions amounts.
     *
     * @return bool
     */
    public function isTransactionsAmountLessThan(iterable $paymentTransactions, float $amount): bool
    {
        if (is_array($paymentTransactions)) {
            if (!count($paymentTransactions)) {
                return false;
            }
        } elseif (!iterator_count($paymentTransactions)) {
            return false;
        }

        return $this->sumTransactionAmounts($paymentTransactions)->isLessThan($amount);
    }

    /**
     * @param iterable<PaymentTransaction> $paymentTransactions
     * @param float $amount Amount to compare with the sum of transactions amounts.
     *
     * @return bool
     */
    public function isTransactionsAmountGreaterThanOrEqual(iterable $paymentTransactions, float $amount): bool
    {
        if (is_array($paymentTransactions)) {
            if (!count($paymentTransactions)) {
                return false;
            }
        } elseif (!iterator_count($paymentTransactions)) {
            return false;
        }

        return $this->sumTransactionAmounts($paymentTransactions)->isGreaterThanOrEqualTo($amount);
    }
}
