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

        $shoppingList = $this->shoppingListManager->getCurrent();

        if (!$shoppingList) {
            return null;
        }

        /** @var AccountUser $accountUser */
        $accountUser = $this->securityFacade->getLoggedUser();
        $lineItems = $this->lineItemRepository
            ->getOneProductLineItemsWithShoppingListNames($product, $accountUser);

        $groupedUnits = [];
        $shoppingListLabels = [];

        if (!count($lineItems)) {
            return [];
        }

        foreach ($lineItems as $lineItem) {
            if (null === $itemShoppingList = $lineItem->getShoppingList()) {
                continue;
            }
            $shoppingListId = $itemShoppingList->getId();
            $groupedUnits[$shoppingListId][] = [
                'line_item_id' => $lineItem->getId(),
                'unit' => $lineItem->getProductUnitCode(),
                'quantity' => $lineItem->getQuantity()
            ];
            if (!isset($shoppingListLabels[$shoppingListId])) {
                $shoppingListLabels[$shoppingListId] = $lineItem->getShoppingList()->getLabel();
            }
        }

        $shoppingListUnits = [];
        $activeShoppingListId = $shoppingList->getId();
        if (isset($groupedUnits[$activeShoppingListId])) {
            $shoppingListUnits[] = [
                'shopping_list_id' => $activeShoppingListId,
                'shopping_list_label' => $shoppingListLabels[$activeShoppingListId],
                'is_current' => true,
                'line_items' => $groupedUnits[$activeShoppingListId],
            ];
        }
        unset($groupedUnits[$activeShoppingListId]);
        foreach ($groupedUnits as $shoppingListId => $lineItems) {
            $shoppingListUnits[] = [
                'shopping_list_id' => $shoppingListId,
                'shopping_list_label' => $shoppingListLabels[$shoppingListId],
                'is_current' => false,
                'line_items' => $lineItems,
            ];
        }

        return $shoppingListUnits;
    }
}
