<?php

namespace OroB2B\Bundle\PricingBundle\Entity;

interface QuantityAwareInterface
{
    /**
     * @return int
     */
    public function getQuantity();
}
