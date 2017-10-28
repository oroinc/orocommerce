<?php

namespace Oro\Bundle\CheckoutBundle\Mapper;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\Order;

interface MapperInterface
{
    /**
     * @param Checkout $checkout
     * @param array $data
     * @param array $skipped
     * @return Order
     */
    public function map(Checkout $checkout, array $data = [], array $skipped = []);
}
