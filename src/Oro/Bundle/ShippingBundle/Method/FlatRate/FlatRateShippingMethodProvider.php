<?php

namespace Oro\Bundle\ShippingBundle\Method\FlatRate;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

class FlatRateShippingMethodProvider implements ShippingMethodProviderInterface
{
    /** @var FlatRateShippingMethod */
    protected $method;

    public function __construct()
    {
        $this->method = new FlatRateShippingMethod();
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingMethods()
    {
        return [$this->method->getIdentifier() => $this->method];
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingMethod($name)
    {
        if ($name === $this->method->getIdentifier()) {
            return $this->method;
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function hasShippingMethod($name)
    {
        return $name === $this->method->getIdentifier();
    }
}
