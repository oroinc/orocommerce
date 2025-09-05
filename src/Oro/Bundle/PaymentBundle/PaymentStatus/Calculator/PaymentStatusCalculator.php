<?php

namespace Oro\Bundle\PaymentBundle\PaymentStatus\Calculator;

use Oro\Bundle\PaymentBundle\PaymentStatus\Context\PaymentStatusCalculationContext;
use Oro\Bundle\PaymentBundle\PaymentStatus\Context\PaymentStatusCalculationContextFactory;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;

/**
 * Calculates the payment status for an entity using a chain of specific status calculators.
 *
 * This class must be used only to calculate the payment status for an entity.
 * In order just to get the current payment status - use {@see PaymentStatusManager}.
 */
class PaymentStatusCalculator implements PaymentStatusCalculatorInterface
{
    /**
     * @param iterable<PaymentStatusCalculatorInterface> $paymentStatusCalculators
     */
    public function __construct(
        private readonly iterable $paymentStatusCalculators,
        private readonly PaymentStatusCalculationContextFactory $paymentStatusCalculationContextFactory
    ) {
    }

    #[\Override]
    public function calculatePaymentStatus(
        object $entity,
        ?PaymentStatusCalculationContext $paymentStatusCalculationContext = null
    ): string {
        $paymentStatusCalculationContext = $paymentStatusCalculationContext ??
            $this->paymentStatusCalculationContextFactory->createPaymentStatusCalculationContext($entity);

        foreach ($this->paymentStatusCalculators as $paymentStatusCalculator) {
            $paymentStatus = $paymentStatusCalculator
                ->calculatePaymentStatus($entity, $paymentStatusCalculationContext);
            if ($paymentStatus !== null) {
                return $paymentStatus;
            }
        }

        return PaymentStatuses::PENDING;
    }
}
