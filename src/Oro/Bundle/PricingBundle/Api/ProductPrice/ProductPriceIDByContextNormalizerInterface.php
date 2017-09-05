<?php

namespace Oro\Bundle\PricingBundle\Api\ProductPrice;

use Oro\Component\ChainProcessor\ContextInterface;

/**
 * Normalizes ProductPrice id by context
 */
interface ProductPriceIDByContextNormalizerInterface
{
    /**
     * @param string           $productPriceID
     * @param ContextInterface $context
     *
     * @return string
     */
    public function normalize(string $productPriceID, ContextInterface $context): string;
}
