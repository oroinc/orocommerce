<?php

namespace Oro\Bundle\ShippingBundle\Method;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;

/**
 * This interface should be implemented by shipping methods that
 * combine price calculations by all shipping method types in optimization purpose.
 */
interface PricesAwareShippingMethodInterface
{
    /**
     * @param ShippingContextInterface $context
     * @param array                    $methodOptions
     * @param array                    $optionsByTypes [shipping method type identifier => option, ...]
     *
     * @return Price[] [shipping method type identifier => price, ...]
     */
    public function calculatePrices(
        ShippingContextInterface $context,
        array $methodOptions,
        array $optionsByTypes
    ): array;
}
