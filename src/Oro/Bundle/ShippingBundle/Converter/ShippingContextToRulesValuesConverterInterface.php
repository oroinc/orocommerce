<?php

namespace Oro\Bundle\ShippingBundle\Converter;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

interface ShippingContextToRulesValuesConverterInterface
{
    public function convert(ShippingContextInterface $context): array;
}
