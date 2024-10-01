<?php

namespace Oro\Bundle\CheckoutBundle\Action;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ShippingBundle\Method\Configuration\PreConfiguredShippingMethodConfigurationInterface;

/**
 * Sets a shipping method for a checkout from a source entity.
 */
class DefaultShippingMethodSetterDecorator implements DefaultShippingMethodSetterInterface
{
    private DefaultShippingMethodSetterInterface $defaultShippingMethodSetter;

    public function __construct(DefaultShippingMethodSetterInterface $defaultShippingMethodSetter)
    {
        $this->defaultShippingMethodSetter = $defaultShippingMethodSetter;
    }

    #[\Override]
    public function setDefaultShippingMethod(Checkout $checkout): void
    {
        if ($checkout->getShippingMethod()) {
            return;
        }

        $sourceEntity = $checkout->getSourceEntity();
        if ($sourceEntity instanceof PreConfiguredShippingMethodConfigurationInterface
            && $sourceEntity->getShippingMethod()
            && $sourceEntity->getShippingMethodType()
        ) {
            $checkout->setShippingMethod($sourceEntity->getShippingMethod());
            $checkout->setShippingMethodType($sourceEntity->getShippingMethodType());
        } else {
            $this->defaultShippingMethodSetter->setDefaultShippingMethod($checkout);
        }
    }
}
