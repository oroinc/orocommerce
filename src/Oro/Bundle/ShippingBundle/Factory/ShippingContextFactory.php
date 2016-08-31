<?php

namespace Oro\Bundle\ShippingBundle\Factory;

use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;

class ShippingContextFactory
{
    /** @var ShippingOriginProvider */
    protected $shippingOriginProvider;

    /**
     * @param ShippingOriginProvider $shippingOriginProvider
     */
    public function __construct(
        ShippingOriginProvider $shippingOriginProvider
    ) {
        $this->shippingOriginProvider = $shippingOriginProvider;
    }

    /**
     * Sets defaults for Shipping Context
     *
     * @return ShippingContext
     */
    public function create()
    {
        $shippingContext = new ShippingContext();

        $shippingContext->setShippingOrigin($this->shippingOriginProvider->getSystemShippingOrigin());

        return $shippingContext;
    }
}
