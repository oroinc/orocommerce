<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Stubs;

use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

class LowInventoryProviderStub extends LowInventoryProvider
{
    const PRODUCT_ID_WITH_ENABLED_LOW_INVENTORY = 1;
    const PRODUCT_ID_WITH_DISABLED_LOW_INVENTORY = 2;

    public function __construct()
    {
    }

    /**
     * @param Product          $product
     * @param ProductUnit|null $productUnit
     *
     * @return bool
     */
    public function isLowInventoryProduct(Product $product, ProductUnit $productUnit = null)
    {
        switch ($product->getId()) {
            case self::PRODUCT_ID_WITH_ENABLED_LOW_INVENTORY:
                return true;
            case self::PRODUCT_ID_WITH_DISABLED_LOW_INVENTORY:
                return false;
            default:
                return false;
        }
    }
}
