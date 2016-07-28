<?php

namespace OroB2B\Bundle\ShippingBundle\Provider;

class ShippingContext implements ShippingContextAwareInterface
{
    /** @var array */
    protected $items = [];

    /**
     * {@inheritdoc}
     */
    public function get($name)
    {
        if (!empty($this->items) && array_key_exists($name, $this->items)) {
            return $this->items[$name];
        }

        return null;
    }
}
