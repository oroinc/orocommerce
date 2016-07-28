<?php

namespace OroB2B\Bundle\ShippingBundle\Provider;

class ShippingContextProvider implements ShippingContextAwareInterface
{
    /**
     * @var array
     */
    protected $shippingContext;

    /**
     * ShippingContextProvider constructor.
     * @param array $shippingContext
     */
    public function __construct(array $shippingContext)
    {
        $this->shippingContext = $shippingContext;
    }

    /**
     * @return array
     */
    public function getShippingContext()
    {
        return $this->shippingContext;
    }

}