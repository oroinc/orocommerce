<?php

namespace OroB2B\Bundle\OrderBundle\SubtotalProcessor;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Model\Subtotal;

interface SubtotalProviderInterface
{
    /**
     * Get provider name
     *
     * @return string
     */
    public function getName();

    /**
     * Get order subtotal
     *
     * @param Order $order
     *
     * @return Subtotal
     */
    public function getSubtotal(Order $order);
}
