<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\PaymentStatus\Calculator;

use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\PaymentStatus\Context\PaymentStatusCalculationContext;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;

/**
 * Calculates the payment status for an entity when the payment is authorized.
 *
 * This class must be used only to calculate the payment status for an entity.
 * In order just to get the current payment status - use {@see PaymentStatusManager}.
 */
class AuthorizedPaymentStatusCalculator implements PaymentStatusCalculatorInterface
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

        $authorizeTransactions = $this->getAuthorizeTransactions($paymentTransactions);

        if (!count($authorizeTransactions)) {
            return null;
        }

        $totalAmount = $total->getAmount();
        $isAuthorized = $this->paymentStatusCalculationHelper
            ->isTransactionsAmountGreaterThanOrEqual($authorizeTransactions, $totalAmount);

        if ($isAuthorized) {
            return PaymentStatuses::AUTHORIZED;
        }

        return null;
    }

    private function getAuthorizeTransactions(iterable $paymentTransactions): array
    {
        $authorizeTransactions = [];
        foreach ($paymentTransactions as $paymentTransaction) {
            if ($paymentTransaction->isClone()) {
                continue;
            }

            if (
                $paymentTransaction->isActive()
                && $paymentTransaction->isSuccessful()
                && $paymentTransaction->getAction() === PaymentMethodInterface::AUTHORIZE
            ) {
                $authorizeTransactions[] = $paymentTransaction;
            }
        }

        return $authorizeTransactions;
    }
}
