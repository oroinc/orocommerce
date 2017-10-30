<?php

namespace Oro\Bundle\InventoryBundle\Twig;

use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\ProductBundle\Entity\Product;

class LowInventoryExtension extends \Twig_Extension
{
    /**
     * @var LowInventoryProvider
     */
    protected $lowInventoryProvider;

    /**
     * @param LowInventoryProvider $lowInventoryProvider
     */
    public function __construct(LowInventoryProvider $lowInventoryProvider)
    {
        $this->lowInventoryProvider = $lowInventoryProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction(
                'oro_is_low_inventory_product',
                [$this, 'isLowInventory']
            )
        ];
    }

    /**
     * @param Product $product
     *
     * @return bool
     */
    public function isLowInventory(Product $product)
    {
        return $this->lowInventoryProvider->isLowInventoryProduct($product);
    }
}
