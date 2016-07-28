<?php

namespace OroB2B\Bundle\ShippingBundle\Provider;

interface ShippingContextAwareInterface
{
    /**
     * Gets a value stored in the context.
     *
     * @param string $name
     * @return mixed|null
     */
    public function get($name);
}
