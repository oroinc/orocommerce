<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\DataProvider\ProductShoppingListsDataProvider;

class FrontendShoppingListProductUnitsQuantityProvider
{
    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var ProductShoppingListsDataProvider
     */
    protected $productShoppingListsDataProvider;

    /**
     * @param ProductShoppingListsDataProvider $productShoppingListsDataProvider
     */
    public function __construct(ProductShoppingListsDataProvider $productShoppingListsDataProvider)
    {
        $this->productShoppingListsDataProvider = $productShoppingListsDataProvider;
    }

    /**
     * @param Product|null $product
     * @return mixed|null
     */
    public function getByProduct(Product $product = null)
    {
        if (!$product) {
            return null;
        }

        $this->setByProducts([$product]);

        return $this->data[$product->getId()];
    }

    /**
     * @param Product[] $products
     * @return array
     */
    public function getByProducts($products)
    {
        $this->setByProducts($products);

        $shoppingLists = [];
        foreach ($products as $product) {
            $productId = $product->getId();
            if ($this->data[$productId]) {
                $shoppingLists[$productId] = $this->data[$productId];
            }
        }

        return $shoppingLists;
    }

    /**
     * @param Product[] $products
     */
    protected function setByProducts($products)
    {
        $products = array_filter($products, function (Product $product) {
            return !array_key_exists($product->getId(), $this->data);
        });
        if (!$products) {
            return;
        }

        $shoppingLists = $this->productShoppingListsDataProvider->getProductsUnitsQuantity($products);
        foreach ($products as $product) {
            $productId = $product->getId();
            $this->data[$productId] = isset($shoppingLists[$productId]) ? $shoppingLists[$productId] : null;
        }
    }
}
