<?php

namespace Oro\Bundle\ShippingBundle\Layout\DataProvider;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;

class ShippingMethodsProvider
{
    /** @var ShippingPriceProvider */
    protected $shippingPriceProvider;

    /** @var ShippingMethodRegistry */
    protected $registry;

    /**
     * @param ShippingPriceProvider $shippingPriceProvider
     * @param ShippingMethodRegistry $registry
     */
    public function __construct(
        ShippingPriceProvider $shippingPriceProvider,
        ShippingMethodRegistry $registry
    ) {
        $this->shippingPriceProvider = $shippingPriceProvider;
        $this->registry = $registry;
    }

    /**
     * @param ShippingContextInterface $context
     * @return array
     */
    public function getMethods(ShippingContextInterface $context)
    {
        return $this->shippingPriceProvider->getApplicableMethodsWithTypesData($context);
    }
}
