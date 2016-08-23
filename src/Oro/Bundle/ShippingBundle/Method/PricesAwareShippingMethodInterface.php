<?php

namespace Oro\Bundle\ShippingBundle\Method;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

interface PricesAwareShippingMethodInterface
{
    /**
     * @param ShippingContextInterface $context
     * @param array $optionsByTypes
     * @return array
     */
    public function calculatePrices(ShippingContextInterface $context, array $optionsByTypes);
}
