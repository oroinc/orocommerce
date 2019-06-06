<?php

namespace Oro\Bundle\InventoryBundle\Twig;

use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to check if the product is a "low inventory" item:
 *   - oro_is_low_inventory_product
 */
class LowInventoryExtension extends AbstractExtension
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
            new TwigFunction(
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
