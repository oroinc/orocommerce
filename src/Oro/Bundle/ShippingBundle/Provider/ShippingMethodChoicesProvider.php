<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

/**
 * Provides choices for form types to select shipping methods.
 */
class ShippingMethodChoicesProvider
{
    public function __construct(private ShippingMethodProviderInterface $shippingMethodProvider)
    {
    }

    public function getMethods(): array
    {
        $result = [];
        $shippingMethods = $this->shippingMethodProvider->getShippingMethods();
        foreach ($shippingMethods as $shippingMethod) {
            if ($shippingMethod->isEnabled() && $this->hasOptionsConfigurationForm($shippingMethod)) {
                $name = $shippingMethod->getName();
                //cannot guarantee uniqueness of shipping name
                //need to be sure that we wouldn't override exists one
                if (array_key_exists($name, $result)) {
                    $name .= $this->getShippingMethodIdLabel($shippingMethod);
                }
                $result[$name] = $shippingMethod->getIdentifier();
            }
        }

        return $result;
    }

    private function hasOptionsConfigurationForm(ShippingMethodInterface $shippingMethod): bool
    {
        if (!$shippingMethod->getOptionsConfigurationFormType()) {
            return false;
        }

        $types = $shippingMethod->getTypes();
        if (!$types) {
            return true;
        }

        foreach ($types as $type) {
            if ($type->getOptionsConfigurationFormType()) {
                return true;
            }
        }

        return false;
    }

    private function getShippingMethodIdLabel(ShippingMethodInterface $shippingMethod): string
    {
        //extract entity identifier flat_rate_4 => 4
        $id = substr($shippingMethod->getIdentifier(), strrpos($shippingMethod->getIdentifier(), '_') + 1);
        return " ($id)";
    }
}
