<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\Model\ProductLineItemPriceCriteriaFactory;

use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;

/**
 * Declares an interface for the factories creating a product price criteria from a product line item.
 */
interface ProductLineItemPriceCriteriaFactoryInterface
{
    public function createFromProductLineItem(
        ProductLineItemInterface $lineItem,
        ?string $currency
    ): ?ProductPriceCriteria;

    public function isSupported(
        ProductLineItemInterface $lineItem,
        ?string $currency
    ): bool;
}
