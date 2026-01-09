<?php

namespace Oro\Bundle\ShippingBundle\Converter;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

/**
 * Defines the contract for converters that transform shipping context into rule evaluation values.
 *
 * Implementations of this interface extract relevant data from the shipping context and convert it into an array format
 * suitable for evaluating shipping rule conditions and expressions.
 */
interface ShippingContextToRulesValuesConverterInterface
{
    public function convert(ShippingContextInterface $context): array;
}
