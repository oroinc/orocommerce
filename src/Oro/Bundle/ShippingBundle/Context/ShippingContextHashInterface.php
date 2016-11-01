<?php

namespace Oro\Bundle\ShippingBundle\Context;

interface ShippingContextHashInterface
{
    /**
     * @return string
     */
    public function generateHash();
}
