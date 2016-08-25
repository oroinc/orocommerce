<?php

namespace Oro\Bundle\OrderBundle\Model;

use Oro\Bundle\CurrencyBundle\Entity\Price;

interface ShippingAwareInterface
{
    /**
     * @return Price|null
     */
    public function getShippingCost();
}
