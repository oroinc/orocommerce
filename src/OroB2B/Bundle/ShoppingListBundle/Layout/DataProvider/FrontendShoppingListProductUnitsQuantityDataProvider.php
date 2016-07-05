<?php

namespace OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class FrontendShoppingListProductUnitsQuantityDataProvider extends AbstractServerRenderDataProvider
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
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        $product = $context->data()->get('product');

        if (null === $product) {
            return null;
        }

        $shoppingList = $this->shoppingListManager->getCurrent();

        if (!$shoppingList) {
            return null;
        }

        return $this->getProductUnitsQuantity($shoppingList, $product);
    }

    /**
     * @param ShoppingList $shoppingList
     * @param Product $product
     * @return array
     */
    protected function getProductUnitsQuantity(ShoppingList $shoppingList, Product $product)
    {
        if (!$product) {
            return null;
        }

        if (!$shoppingList) {
            return null;
        }
        /** @var AccountUser $accountUser */
        $accountUser = $this->securityFacade->getLoggedUser();
        $lineItems = $this->lineItemRepository
            ->getOneProductItemsWithShoppingListNames($product, $accountUser);

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
            $groupedUnits[$shoppingListId][] =
                [
                    'unit' => $lineItem->getProductUnitCode(),
                    'quantity' => $lineItem->getQuantity()
                ];
            if (!isset($shoppingListLabels[$shoppingListId])) {
                $shoppingListLabels[$shoppingListId] = $lineItem->getShoppingList()->getLabel();
            }
        }

        $shoppingListUnits = [];
        $activeShoppingListId = $shoppingList->getId();
        $shoppingListUnits[] = [
            'shopping_list_id' => $activeShoppingListId ,
            'shopping_list_label' => $shoppingListLabels[$activeShoppingListId],
            'line_items' => $groupedUnits[$activeShoppingListId],
        ];
        unset($groupedUnits[$activeShoppingListId]);
        foreach ($groupedUnits as $shoppingListId => $lineItems) {
            $shoppingListUnits[] = [
                'shopping_list_id' => $shoppingListId,
                'shopping_list_label' => $shoppingListLabels[$shoppingListId],
                'line_items' => $lineItems,
            ];
        }

        return $shoppingListUnits;
    }
}
