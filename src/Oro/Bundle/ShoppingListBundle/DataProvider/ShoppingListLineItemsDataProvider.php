<?php

namespace Oro\Bundle\ShoppingListBundle\DataProvider;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\Collection;
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
    protected array $lineItems = [];
    protected array $allLineItems = [];

    public function __construct(private ManagerRegistry $registry)
    {
    }

    public function getAllShoppingListLineItems(ShoppingList $shoppingList): array
    {
        $shoppingListId = $shoppingList->getId();
        if (\array_key_exists($shoppingListId, $this->allLineItems)) {
            return $this->allLineItems[$shoppingListId];
        }

        if ($this->isInitializedWithProducts($shoppingList)) {
            $lineItems = $shoppingList->getLineItems()->toArray();
            $savedForLaterLineItems = $shoppingList->getSavedForLaterLineItems()->toArray();
            $lineItems = \array_merge($lineItems, $savedForLaterLineItems);
        } else {
            $lineItems = $this->registry
                ->getRepository(LineItem::class)
                ->getAllItemsWithProductByShoppingList($shoppingList);
        }

        $this->allLineItems[$shoppingListId] = $lineItems;

        return $lineItems;
    }

    public function getShoppingListLineItems(ShoppingList $shoppingList): array
    {
        $shoppingListId = $shoppingList->getId();
        if (\array_key_exists($shoppingListId, $this->lineItems)) {
            return $this->lineItems[$shoppingListId];
        }

        if ($this->isInitializedCollection($shoppingList->getLineItems())) {
            $lineItems = $shoppingList->getLineItems()->toArray();
        } else {
            $lineItems = $this->registry
                ->getRepository(LineItem::class)
                ->getItemsWithProductByShoppingList($shoppingList);
        }

        $this->lineItems[$shoppingListId] = $lineItems;

        return $lineItems;
    }

    /**
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

        return \array_values($productsWithVariants);
    }

    /**
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

    /**
     * Checks if shopping list line items collection is initialized along with their products.
     */
    private function isInitializedWithProducts(ShoppingList $shoppingList): bool
    {
        $isInitializedItems = $this->isInitializedCollection($shoppingList->getLineItems());
        $isInitializedSavedForLaterItems = $this->isInitializedCollection($shoppingList->getSavedForLaterLineItems());

        return $isInitializedItems && $isInitializedSavedForLaterItems;
    }

    private function isInitializedCollection(Collection $lineItems): bool
    {
        $isInitialized = true;
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
}
