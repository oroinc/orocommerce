<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Service\SingleUnitModeService;
use Oro\Bundle\ProductBundle\Visibility\ProductUnitFieldsSettingsInterface;
use Oro\Bundle\ProductBundle\Visibility\UnitVisibilityInterface;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ProductUnitCodeVisibilityProvider
{
    /** @var SingleUnitModeService */
    private $productUnitFieldsSettings;

    /** @var UnitVisibilityInterface */
    private $unitVisibility;

    /**
     * @param ProductUnitFieldsSettingsInterface $productUnitFieldsSettings
     * @param UnitVisibilityInterface $unitVisibility
     */
    public function __construct(
        ProductUnitFieldsSettingsInterface $productUnitFieldsSettings,
        UnitVisibilityInterface $unitVisibility
    ) {
        $this->productUnitFieldsSettings = $productUnitFieldsSettings;
        $this->unitVisibility = $unitVisibility;
    }

    /**
     * @param ShoppingList|null $shoppingList
     * @return array
     */
    public function getProductsUnitSelectionVisibilities(ShoppingList $shoppingList = null)
    {
        if (!$shoppingList) {
            return [];
        }

        $visibilities = [];
        foreach ($shoppingList->getLineItems() as $lineItem) {
            $product = $lineItem->getProduct();

            $visibilities[$product->getId()] = $this->productUnitFieldsSettings
                ->isProductUnitSelectionVisible($product);
        }

        return $visibilities;
    }

    /**
     * @param ShoppingList|null $shoppingList
     * @return array
     */
    public function getLineItemsUnitVisibilities(ShoppingList $shoppingList = null)
    {
        if (!$shoppingList) {
            return [];
        }

        $visibilities = [];
        foreach ($shoppingList->getLineItems() as $lineItem) {
            $visibilities[$lineItem->getId()] = $this->unitVisibility
                ->isUnitCodeVisible($lineItem->getProductUnitCode());
        }

        return $visibilities;
    }
}
