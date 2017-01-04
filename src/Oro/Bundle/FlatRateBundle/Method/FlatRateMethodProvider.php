<?php

namespace Oro\Bundle\FlatRateBundle\Method;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

class FlatRateMethodProvider implements ShippingMethodProviderInterface
{
    /** @var FlatRateMethod */
    protected $method;

    public function __construct()
    {
        $this->method = new FlatRateMethod();
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
