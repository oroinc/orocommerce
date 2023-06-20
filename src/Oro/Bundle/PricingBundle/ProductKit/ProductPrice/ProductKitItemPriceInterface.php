<?php

declare(strict_types=1);

namespace Oro\Bundle\PricingBundle\ProductKit\ProductPrice;

use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
use Oro\Bundle\ProductBundle\Entity\ProductKitItem;

/**
 * Declares set of methods of the product kit item price model.
 */
interface ProductKitItemPriceInterface extends ProductPriceInterface
{
    public function getKitItem(): ProductKitItem;
}
