<?php

namespace Oro\Bundle\ShoppingListBundle\DataProvider;

use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListLineItemsDataProvider
{
    /**
     * @var array
     */
    protected $lineItems = [];

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param ShoppingList $shoppingList
     * @return \Oro\Bundle\ShoppingListBundle\Entity\LineItem[]
     */
    public function getShoppingListLineItems(ShoppingList $shoppingList)
    {
        $shoppingListId = $shoppingList->getId();
        if (array_key_exists($shoppingListId, $this->lineItems)) {
            return $this->lineItems[$shoppingListId];
        }
        /** @var LineItemRepository $repository */
        $repository = $this->registry->getManagerForClass('OroShoppingListBundle:LineItem')
            ->getRepository('OroShoppingListBundle:LineItem');
        $lineItems = $repository->getItemsWithProductByShoppingList($shoppingList);
        $this->lineItems[$shoppingListId] = $lineItems;
        return $lineItems;
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
