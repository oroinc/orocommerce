<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

/**
 * Provides choices for form types to select shipping methods.
 */
class ShippingMethodChoicesProvider
{
    private ShippingMethodProviderInterface $shippingMethodProvider;

    public function __construct(ShippingMethodProviderInterface $shippingMethodProvider)
    {
        $this->shippingMethodProvider = $shippingMethodProvider;
    }

    public function getMethods(): array
    {
        $result = [];
        $shippingMethods = $this->shippingMethodProvider->getShippingMethods();
        foreach ($shippingMethods as $shippingMethod) {
            if ($shippingMethod->isEnabled() && $this->hasOptionsConfigurationForm($shippingMethod)) {
                $result[$shippingMethod->getLabel()] = $shippingMethod->getIdentifier();
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
}
