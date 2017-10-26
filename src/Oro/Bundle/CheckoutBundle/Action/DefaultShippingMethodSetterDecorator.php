<?php

namespace Oro\Bundle\CheckoutBundle\Action;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ShippingBundle\Method\Configuration\PreConfiguredShippingMethodConfigurationInterface;

class DefaultShippingMethodSetterDecorator
{
    /**
     * @var DefaultShippingMethodSetter
     */
    private $defaultShippingMethodSetter;

    /**
     * @param DefaultShippingMethodSetter $defaultShippingMethodSetter
     */
    public function __construct(DefaultShippingMethodSetter $defaultShippingMethodSetter)
    {
        $this->defaultShippingMethodSetter = $defaultShippingMethodSetter;
    }

    /**
     * @param Checkout $checkout
     */
    public function setDefaultShippingMethod(Checkout $checkout)
    {
        if ($checkout->getShippingMethod()) {
            return;
        }
        $sourceEntity = $checkout->getSourceEntity();

        if ($sourceEntity instanceof PreConfiguredShippingMethodConfigurationInterface) {
            $checkout->setShippingMethod($sourceEntity->getShippingMethod());
        } else {
            $this->defaultShippingMethodSetter->setDefaultShippingMethod($checkout);
        }
    }
}
