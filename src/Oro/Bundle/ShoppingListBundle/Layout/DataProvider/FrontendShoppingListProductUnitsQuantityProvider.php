<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider;

/**
 * Provides information about shopping lists to which a product can be added.
 */
class FrontendShoppingListProductUnitsQuantityProvider
{
    private ProductShoppingListsDataProvider $productShoppingListsDataProvider;

    /** @var array [product id => [shopping list data (array), ...] or null, ...] */
    private array $shoppingLists = [];

    public function __construct(ProductShoppingListsDataProvider $productShoppingListsDataProvider)
    {
        $this->productShoppingListsDataProvider = $productShoppingListsDataProvider;
    }

    /**
     * @param Product|ProductView|null $product
     *
     * @return array|null [shopping list data (array), ...]
     */
    public function getByProduct(Product|ProductView|null $product): ?array
    {
        if (null === $product) {
            return null;
        }

        $productId = $product->getId();
        $this->setByProducts([$productId]);

        return $this->shoppingLists[$productId];
    }

    /**
     * @param ProductView[] $products
     *
     * @return array [product id => [shopping list data (array), ...] or null, ...]
     */
    public function getByProducts(array $products): array
    {
        $productIds = array_map(function (ProductView $product) {
            return $product->getId();
        }, $products);
        $this->setByProducts($productIds);

        $shoppingLists = [];
        foreach ($productIds as $productId) {
            if ($this->shoppingLists[$productId]) {
                $shoppingLists[$productId] = $this->shoppingLists[$productId];
            }
        }

        return $shoppingLists;
    }

    /**
     * @param int[] $productIds
     */
    private function setByProducts(array $productIds): void
    {
        $productIds = array_filter($productIds, function (int $productId) {
            return !\array_key_exists($productId, $this->shoppingLists);
        });
        if (!$productIds) {
            return;
        }

        $shoppingLists = $this->productShoppingListsDataProvider->getProductsUnitsQuantity($productIds);
        foreach ($productIds as $productId) {
            $this->shoppingLists[$productId] = $shoppingLists[$productId] ?? null;
        }
    }
}
