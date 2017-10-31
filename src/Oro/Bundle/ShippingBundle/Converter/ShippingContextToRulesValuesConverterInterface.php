<?php

namespace Oro\Bundle\ShippingBundle\Converter;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

interface ShippingContextToRulesValuesConverterInterface
{
    /**
     * @param ShippingContextInterface $context
     * @return array
     */
    public function convert(ShippingContextInterface $context): array;
}
