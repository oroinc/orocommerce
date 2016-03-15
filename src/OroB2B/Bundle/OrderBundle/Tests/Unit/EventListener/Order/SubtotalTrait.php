<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;

trait SubtotalTrait
{
    /**
     * @param string $type
     * @param string $label
     * @param float $amount
     * @param string $currency
     * @param bool $visible
     * @return Subtotal
     */
    protected function getSubtotal($type, $label, $amount, $currency, $visible)
    {
        $subtotal = new Subtotal();
        $subtotal
            ->setType($type)
            ->setLabel($label)
            ->setAmount($amount)
            ->setCurrency($currency)
            ->setVisible($visible);

        return $subtotal;
    }
}
