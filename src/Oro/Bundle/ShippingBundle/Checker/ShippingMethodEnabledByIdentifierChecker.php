<?php

namespace Oro\Bundle\ShippingBundle\Checker;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;

class ShippingMethodEnabledByIdentifierChecker implements ShippingMethodEnabledByIdentifierCheckerInterface
{
    /**
     * @var ShippingMethodProviderInterface
     */
    private $shippingMethodProvider;

    public function __construct(ShippingMethodProviderInterface $shippingMethodProvider)
    {
        $this->shippingMethodProvider = $shippingMethodProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled($identifier)
    {
        return $this->shippingMethodProvider->getShippingMethod($identifier) !== null ?
            $this->shippingMethodProvider->getShippingMethod($identifier)->isEnabled() :
            false;
    }
}
