<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\ShippingBundle\Provider\SystemShippingOriginProvider;

/**
 * Gets a shipping origin from system configuration.
 */
class ConfigCheckoutShippingOriginProvider implements CheckoutShippingOriginProviderInterface
{
    private SystemShippingOriginProvider $systemShippingOriginProvider;

    public function __construct(SystemShippingOriginProvider $systemShippingOriginProvider)
    {
        $this->systemShippingOriginProvider = $systemShippingOriginProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingOrigin(Checkout $checkout): ShippingOrigin
    {
        return $this->systemShippingOriginProvider->getSystemShippingOrigin();
    }
}
