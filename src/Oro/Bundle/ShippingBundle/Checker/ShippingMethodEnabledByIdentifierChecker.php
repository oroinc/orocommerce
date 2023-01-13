<?php

namespace Oro\Bundle\ShippingBundle\Checker;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

/**
 * The default implementation of the service to check whether a shipping method
 * with a specific identifier is enabled or not.
 */
class ShippingMethodEnabledByIdentifierChecker implements ShippingMethodEnabledByIdentifierCheckerInterface
{
    private ShippingMethodProviderInterface $shippingMethodProvider;

    public function __construct(ShippingMethodProviderInterface $shippingMethodProvider)
    {
        $this->shippingMethodProvider = $shippingMethodProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled(string $identifier): bool
    {
        $shippingMethod = $this->shippingMethodProvider->getShippingMethod($identifier);

        return null !== $shippingMethod && $shippingMethod->isEnabled();
    }
}
