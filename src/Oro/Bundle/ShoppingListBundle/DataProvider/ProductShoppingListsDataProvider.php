<?php

namespace Oro\Bundle\ShoppingListBundle\DataProvider;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\ProductBundle\Entity\Product;
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
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param ShoppingListManager $shoppingListManager
     * @param LineItemRepository $lineItemRepository
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        ShoppingListManager $shoppingListManager,
        LineItemRepository $lineItemRepository,
        SecurityFacade $securityFacade
    ) {
        $this->shoppingListManager = $shoppingListManager;
        $this->lineItemRepository = $lineItemRepository;
        $this->securityFacade = $securityFacade;
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

        /** @var AccountUser $accountUser */
        $accountUser = $this->securityFacade->getLoggedUser();
        $lineItems = $this->lineItemRepository
            ->getProductItemsWithShoppingListNames($products, $accountUser);
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
