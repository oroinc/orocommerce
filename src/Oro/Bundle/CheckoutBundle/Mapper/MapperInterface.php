<?php

namespace Oro\Bundle\CheckoutBundle\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\Order;

interface MapperInterface
{
    /**
     * @param Checkout $checkout
     * @param array $data
     * @return Order
     */
    public function map(Checkout $checkout, array $data = []);
}
