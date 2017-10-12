<?php

namespace Oro\Bundle\InventoryBundle\Twig;

use Oro\Bundle\InventoryBundle\Inventory\LowInventoryQuantityManager;
use Oro\Bundle\ProductBundle\Entity\Product;

class LowInventoryExtension extends \Twig_Extension
{
    /**
     * @var LowInventoryQuantityManager
     */
    protected $lowInventoryQuantityManager;

    /**
     * @param LowInventoryQuantityManager $lowInventoryQuantityManager
     */
    public function __construct(LowInventoryQuantityManager $lowInventoryQuantityManager)
    {
        $this->lowInventoryQuantityManager = $lowInventoryQuantityManager;
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
        return $this->lowInventoryQuantityManager->isLowInventoryProduct($product);
    }
}
