<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class SingleUnitModeProvider
{
    /** @var SingleUnitModeService */
    private $singleUnitService;

    /**
     * @param SingleUnitModeService $singleUnitService
     */
    public function __construct(SingleUnitModeService $singleUnitService)
    {
        $this->singleUnitService = $singleUnitService;
    }

    /**
     * @param ShoppingList|null $shoppingList
     * @return array
     */
    public function getProductStates(ShoppingList $shoppingList = null)
    {
        if (!$shoppingList) {
            return [];
        }

        $states = [];
        foreach ($shoppingList->getLineItems() as $lineItem) {
            $product = $lineItem->getProduct();

            $states[$product->getId()] = $this->singleUnitService->isProductPrimaryUnitSingleAndDefault($product);
        }

        return $states;
    }
}
