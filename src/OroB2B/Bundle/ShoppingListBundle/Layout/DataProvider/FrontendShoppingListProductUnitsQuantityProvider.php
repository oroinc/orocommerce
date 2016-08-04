<?php

namespace OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class FrontendShoppingListProductUnitsQuantityProvider
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
     * @param ShoppingListManager $shoppingListManager
     * @param LineItemRepository $lineItemRepository
     */
    public function __construct(ShoppingListManager $shoppingListManager, LineItemRepository $lineItemRepository)
    {
        $this->shoppingListManager = $shoppingListManager;
        $this->lineItemRepository = $lineItemRepository;
    }

    /**
     * @param Product $product
     * @return array
     */
    public function getProductUnitsQuantity(Product $product = null)
    {
        if (!$product) {
            return null;
        }

        $shoppingList = $this->shoppingListManager->getCurrent();
        if (!$shoppingList) {
            return null;
        }
        
        $items = $this->lineItemRepository->getItemsByShoppingListAndProduct($shoppingList, $product);
        $units = [];

        foreach ($items as $item) {
            $units[$item->getProductUnitCode()] = $item->getQuantity();
        }

        return $units;
    }
}
