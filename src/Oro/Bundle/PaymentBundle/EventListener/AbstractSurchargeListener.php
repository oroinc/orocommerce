<?php

namespace Oro\Bundle\PaymentBundle\EventListener;

use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;

abstract class AbstractSurchargeListener
{
    /**
     * @param Subtotal|Subtotal[] $subtotals
     * @return float
     */
    protected function getSubtotalAmount($subtotals)
    {
        if (!is_array($subtotals)) {
            $subtotals = [$subtotals];
        }

        // TODO: BB-3274 Need to check and convert currency for subtotals
        $amount = 0;
        foreach ($subtotals as $subtotal) {
            if ($subtotal->getOperation() === Subtotal::OPERATION_ADD) {
                $amount += $subtotal->getAmount();
            } elseif ($subtotal->getOperation() === Subtotal::OPERATION_SUBTRACTION) {
                $amount -= $subtotal->getAmount();
            }
        }

        return $amount;
    }
}
