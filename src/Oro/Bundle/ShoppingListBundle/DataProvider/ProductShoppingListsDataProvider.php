<?php

namespace Oro\Bundle\ShoppingListBundle\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class ProductShoppingListsDataProvider
{
    /**
     * @var ShoppingListManager
     */
    protected $shoppingListManager;

    /**
     * @var LineItemRepository
     */
    protected $lineItemRepository;

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @param ShoppingListManager $shoppingListManager
     * @param LineItemRepository $lineItemRepository
     * @param AclHelper $aclHelper
     */
    public function __construct(
        ShoppingListManager $shoppingListManager,
        LineItemRepository $lineItemRepository,
        AclHelper $aclHelper
    ) {
        $this->shoppingListManager = $shoppingListManager;
        $this->lineItemRepository = $lineItemRepository;
        $this->aclHelper = $aclHelper;
    }
    
    /**
     * @param Product|null $product
     * @return array
     */
    public function getProductUnitsQuantity($product)
    {
        if (!$product) {
            return null;
        }

        $shoppingLists = $this->getProductsUnitsQuantity([$product]);
        return isset($shoppingLists[$product->getId()]) ? $shoppingLists[$product->getId()] : null;
    }

    /**
     * @param Product[] $products
     * @return array
     */
    public function getProductsUnitsQuantity($products)
    {
        $currentShoppingList = $this->shoppingListManager->getCurrent();
        if (!$currentShoppingList) {
            return [];
        }
        $currentShoppingListId = $currentShoppingList->getId();

        $lineItems = $this->lineItemRepository
            ->getProductItemsWithShoppingListNames($this->aclHelper, $products);
        if (!count($lineItems)) {
            return [];
        }

        $shoppingLists = [];
        foreach ($lineItems as $lineItem) {
            $shoppingList = $lineItem->getShoppingList();
            $shoppingListId = $shoppingList->getId();
            $product = $lineItem->getProduct();
            $productId = $product->getId();

            $productShoppingLists = isset($shoppingLists[$productId]) ? $shoppingLists[$productId] : [];
            if (!isset($productShoppingLists[$shoppingListId])) {
                $productShoppingLists[$shoppingListId] = [
                    'id' => $shoppingListId,
                    'label' => $shoppingList->getLabel(),
                    'is_current' => $shoppingList->isCurrent(),
                    'line_items' => [],
                ];
            }

            $productShoppingLists[$shoppingListId]['line_items'][] = [
                'id' => $lineItem->getId(),
                'unit' => $lineItem->getProductUnitCode(),
                'quantity' => $lineItem->getQuantity()
            ];

            $shoppingLists[$productId] = $productShoppingLists;
        }

        foreach ($shoppingLists as $productId => $productShoppingLists) {
            if (isset($productShoppingLists[$currentShoppingListId])) {
                $currentShoppingList = $productShoppingLists[$currentShoppingListId];
                unset($productShoppingLists[$currentShoppingListId]);
                array_unshift($productShoppingLists, $currentShoppingList);
            }
            $shoppingLists[$productId] = array_values($productShoppingLists);
        }

        return $shoppingLists;
    }
}
