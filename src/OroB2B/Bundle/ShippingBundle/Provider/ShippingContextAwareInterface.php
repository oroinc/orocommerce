<?php

namespace OroB2B\Bundle\ShippingBundle\Provider;

interface ShippingContextAwareInterface
{
    /**
     * @return array
     */
    public function getShippingContext();
}
