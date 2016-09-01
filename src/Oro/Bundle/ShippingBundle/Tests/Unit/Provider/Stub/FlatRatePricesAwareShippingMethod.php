<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\FlatRate\FlatRateShippingMethod;
use Oro\Bundle\ShippingBundle\Method\PricesAwareShippingMethodInterface;

class FlatRatePricesAwareShippingMethod extends FlatRateShippingMethod implements PricesAwareShippingMethodInterface
{

    /**
     * @param ShippingContextInterface $context
     * @param array $methodOptions
     * @param array $optionsByTypes
     * @return array
     */
    public function calculatePrices(ShippingContextInterface $context, array $methodOptions, array $optionsByTypes)
    {
        return ['primary' => $this->getType('primary')->calculatePrice($context, [], [])];
    }
}
