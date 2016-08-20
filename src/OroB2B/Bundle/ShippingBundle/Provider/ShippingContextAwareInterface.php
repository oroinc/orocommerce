<?php

namespace Oro\Bundle\ShippingBundle\Provider;

interface ShippingContextAwareInterface
{
    /**
     * @return array
     */
    public function getShippingContext();
}
