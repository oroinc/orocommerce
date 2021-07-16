<?php

namespace Oro\Bundle\ShoppingListBundle\DataProvider;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Provides initialized shopping list line items for given shopping list.
 */
class ShoppingListLineItemsDataProvider
{
    /**
     * @var array
     */
    protected $lineItems = [];

    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param ShoppingList $shoppingList
     * @return LineItem[]
     */
    public function getShoppingListLineItems(ShoppingList $shoppingList): array
    {
        $shoppingListId = $shoppingList->getId();
        if (array_key_exists($shoppingListId, $this->lineItems)) {
            return $this->lineItems[$shoppingListId];
        }

        if ($this->isInitializedWithProducts($shoppingList)) {
            $lineItems = $shoppingList->getLineItems()->toArray();
        } else {
            $repository = $this->registry->getManagerForClass(LineItem::class)->getRepository(LineItem::class);
            $lineItems = $repository->getItemsWithProductByShoppingList($shoppingList);
        }

        $this->lineItems[$shoppingListId] = $lineItems;

        return $lineItems;
    }

    /**
     * Checks if shopping list line items collection is initialized along with their products.
     */
    private function isInitializedWithProducts(ShoppingList $shoppingList): bool
    {
        $isInitialized = true;
        $lineItems = $shoppingList->getLineItems();
        if ($lineItems instanceof AbstractLazyCollection && !$lineItems->isInitialized()) {
            $isInitialized = false;
        } elseif ($lineItems->count()) {
            $product = $lineItems->first()->getProduct();
            if ($product instanceof Proxy && !$product->__isInitialized()) {
                $isInitialized = false;
            }
        }

        return $isInitialized;
    }

    /**
     * @param LineItem[] $lineItems
     * @return Product[]
     */
    public function getProductsWithConfigurableVariants(array $lineItems)
    {
        $productsWithVariants = [];

        foreach ($lineItems as $lineItem) {
            $product = $lineItem->getProduct();
            if (isset($productsWithVariants[$product->getId()])) {
                continue;
            }

            if ($parentProduct = $lineItem->getParentProduct()) {
                foreach ($this->getProductVariants($parentProduct) as $variant) {
                    if (!isset($productsWithVariants[$variant->getId()])) {
                        $productsWithVariants[$variant->getId()] = $variant;
                    }
                }

                continue;
            }

            $productsWithVariants[$product->getId()] = $product;
        }

        return array_values($productsWithVariants);
    }

    /**
     * @param Product $product
     * @return Product[]
     */
    protected function getProductVariants(Product $product)
    {
        $variants = [];
        $variantLinks = $product->getVariantLinks();
        foreach ($variantLinks as $variantLink) {
            $variants[] = $variantLink->getProduct();
        }

        return $variants;
    }
}
