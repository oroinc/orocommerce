<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Provider;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentTransactionRepository;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\Calculator\PaymentStatusCalculationHelper;
use Oro\Component\Math\BigDecimal;

/**
 * Provides the amount available to refund for a payment transaction.
 */
class PaymentTransactionAmountAvailableToRefundProvider
{
    public function __construct(
        private readonly PaymentTransactionRepository $transactionRepository,
        private readonly PaymentStatusCalculationHelper $paymentStatusCalculationHelper,
        private readonly RoundingServiceInterface $roundingService
    ) {
    }

    public function getAvailableAmountToRefund(PaymentTransaction $sourceTransaction): float
    {
        $paymentTransactions = $this->transactionRepository
            ->findSuccessfulRelatedTransactionsByAction($sourceTransaction, PaymentMethodInterface::REFUND);

        $refundedAmount = $this->paymentStatusCalculationHelper->sumTransactionAmounts($paymentTransactions);
        $remainingAmount = BigDecimal::of($sourceTransaction->getAmount())->minus($refundedAmount);
        $refundedAmountFloat = max(0.0, $remainingAmount->toFloat());

        return $this->roundingService->round($refundedAmountFloat);
    }
}
