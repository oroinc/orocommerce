<?php

namespace Oro\Bundle\InventoryBundle\Layout\DataProvider;

use Oro\Bundle\InventoryBundle\Inventory\LowInventoryQuantityManager;
use Oro\Bundle\ProductBundle\Entity\Product;

class LowInventoryProvider
{
    /**
     * @var LowInventoryQuantityManager
     */
    protected $lowInventoryQuantityManager;

    /**
     * @param LowInventoryQuantityManager $lowInventoryQuantityManager
     */
    public function __construct(
        LowInventoryQuantityManager $lowInventoryQuantityManager
    ) {
        $this->lowInventoryQuantityManager = $lowInventoryQuantityManager;
    }

    /**
     * Get customer address form view
     *
     * @param Product $product
     *
     * @return bool
     */
    public function isLowInventory(Product $product)
    {
        return $this->lowInventoryQuantityManager->isLowInventoryProduct($product);
    }
}
