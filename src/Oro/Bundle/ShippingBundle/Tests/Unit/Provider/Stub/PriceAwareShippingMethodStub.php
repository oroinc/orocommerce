<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\PricesAwareShippingMethodInterface;

class PriceAwareShippingMethodStub extends ShippingMethodStub implements PricesAwareShippingMethodInterface
{
    /**
     * @inheritDoc
     */
    public function calculatePrices(ShippingContextInterface $context, array $methodOptions, array $optionsByTypes)
    {
        return array_combine(array_keys($optionsByTypes), array_map(function ($options) {
            return $options['aware_price'];
        }, $optionsByTypes));
    }
}
