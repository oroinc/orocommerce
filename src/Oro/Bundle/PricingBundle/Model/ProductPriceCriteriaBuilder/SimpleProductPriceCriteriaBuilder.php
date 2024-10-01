<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Model\ProductPriceCriteriaBuilder;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Creates {@see ProductPriceCriteria}.
 *
 * @method ProductPriceCriteria create()
 */
class SimpleProductPriceCriteriaBuilder extends AbstractProductPriceCriteriaBuilder
{
    #[\Override]
    protected function doCreate(): ProductPriceCriteria
    {
        return new ProductPriceCriteria(
            $this->product,
            $this->productUnit,
            $this->quantity,
            $this->getCurrencyWithFallback()
        );
    }

    #[\Override]
    public function isSupported(Product $product): bool
    {
        return true;
    }
}
