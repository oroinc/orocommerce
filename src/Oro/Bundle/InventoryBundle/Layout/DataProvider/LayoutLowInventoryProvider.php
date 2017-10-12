<?php

namespace Oro\Bundle\InventoryBundle\Layout\DataProvider;

use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\ProductBundle\Entity\Product;

class LayoutLowInventoryProvider
{
    /**
     * @var LowInventoryProvider
     */
    protected $lowInventoryProvider;

    /**
     * @param LowInventoryProvider $lowInventoryProvider
     */
    public function __construct(
        LowInventoryProvider $lowInventoryProvider
    ) {
        $this->lowInventoryProvider = $lowInventoryProvider;
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
        return $this->lowInventoryProvider->isLowInventoryProduct($product);
    }
}
