<?php

namespace Oro\Bundle\PaymentBundle\PaymentStatus\Calculator;

use Oro\Bundle\PaymentBundle\PaymentStatus\Context\PaymentStatusCalculationContext;

/**
 * Calculates the payment status for an entity.
 *
 * Do not use directly, use {@see PaymentStatusManager} instead.
 */
interface PaymentStatusCalculatorInterface
{
    /**
     * Calculates the payment status for the given entity.
     *
     * @param object $entity Entity for which the payment status should be calculated.
     * @param PaymentStatusCalculationContext|null $paymentStatusCalculationContext Payment status calculation context.
     *
     * @return string|null Payment status of the entity, or null if this calculator cannot determine the status.
     */
    public function calculatePaymentStatus(
        object $entity,
        ?PaymentStatusCalculationContext $paymentStatusCalculationContext = null
    ): ?string;
}
