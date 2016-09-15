<?php

namespace Oro\Bundle\ShippingBundle\Method;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

/**
 * Interface PricesAwareShippingMethodInterface
 * Combines price calculations by all Shipping Method's Types in optimization purpose.
 */
interface PricesAwareShippingMethodInterface
{
    /**
     * @param ShippingContextInterface $context
     * @param array $methodOptions
     * @param array $optionsByTypes
     * @return array
     */
    public function calculatePrices(ShippingContextInterface $context, array $methodOptions, array $optionsByTypes);
}
