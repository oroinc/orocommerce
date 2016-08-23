<?php

namespace Oro\Bundle\ShippingBundle\Method;

use Oro\Bundle\ShippingBundle\Provider\ShippingContextAwareInterface;

interface PricesAwareShippingMethodInterface
{
    /**
     * @param ShippingContextAwareInterface $context
     * @param array $optionsByTypes
     * @return array
     */
    public function calculatePrices(ShippingContextAwareInterface $context, array $optionsByTypes);
}
