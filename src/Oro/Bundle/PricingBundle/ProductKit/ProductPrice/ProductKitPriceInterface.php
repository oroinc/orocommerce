<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\ProductKit\ProductPrice;

use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;

/**
 * Declares set of methods for the product kit price model.
 */
interface ProductKitPriceInterface extends ProductPriceInterface
{
    /**
     * @return array<int,ProductKitItemPriceInterface>
     */
    public function getKitItemPrices(): array;

    public function getKitItemPrice(ProductKitItem $productKitItem): ?ProductKitItemPriceInterface;
}
