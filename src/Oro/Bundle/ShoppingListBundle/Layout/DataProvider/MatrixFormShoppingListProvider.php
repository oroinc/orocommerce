<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Layout\DataProvider\ProductFormAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class MatrixFormShoppingListProvider
{
    /** @var MatrixGridOrderFormProvider */
    private $matrixGridOrderFormProvider;

    /** @var ProductFormAvailabilityProvider */
    private $productFormAvailabilityProvider;

    /**
     * @param MatrixGridOrderFormProvider $matrixGridOrderFormProvider
     * @param ProductFormAvailabilityProvider $productFormAvailabilityProvider
     */
    public function __construct(
        MatrixGridOrderFormProvider $matrixGridOrderFormProvider,
        ProductFormAvailabilityProvider $productFormAvailabilityProvider
    ) {
        $this->matrixGridOrderFormProvider = $matrixGridOrderFormProvider;
        $this->productFormAvailabilityProvider = $productFormAvailabilityProvider;
    }

    /**
     * @param ShoppingList $shoppingList
     * @return array
     */
    public function getSortedLineItems(ShoppingList $shoppingList)
    {
        $sortedLineItems = [];
        $productVariants = [];

        foreach ($shoppingList->getLineItems() as $lineItem) {
            $product = $lineItem->getParentProduct() ?: $lineItem->getProduct();
            $lineItemKey = $this->getLineItemKey($product->getId(), $lineItem->getProductUnitCode());

            if (!isset($sortedLineItems[$lineItemKey])) {
                $matrixFormType = $this->getAvailableMatrixFormType($product, $lineItem);

                if ($matrixFormType === Configuration::MATRIX_FORM_INLINE) {
                    // Add matrix form view to line item data for applicable configurable products
                    $sortedLineItems[$lineItemKey]['matrixForm'] =
                        $this->matrixGridOrderFormProvider->getMatrixOrderFormView($product, $shoppingList);
                } elseif ($matrixFormType === Configuration::MATRIX_FORM_POPUP) {
                    $sortedLineItems[$lineItemKey] = [];
                } elseif ($lineItem->getParentProduct()) {
                    // If matrix form is not available for configurable product, group its variants together
                    $this->addLineItemData(
                        $productVariants[$lineItem->getParentProduct()->getId()],
                        $lineItem,
                        $lineItem->getProduct()
                    );
                    continue;
                }
            }

            // For regular products line item data will be added as usual
            $this->addLineItemData($sortedLineItems, $lineItem, $product);
        }

        // Add grouped product variants to the beginning of the shopping list
        foreach (array_reverse($productVariants) as $productVariant) {
            $sortedLineItems = $productVariant + $sortedLineItems;
        }

        return $sortedLineItems;
    }

    /**
     * @param array $array
     * @param LineItem $lineItem
     * @param Product $product
     */
    protected function addLineItemData(&$array, LineItem $lineItem, Product $product)
    {
        $lineItemKey = $this->getLineItemKey($product->getId(), $lineItem->getProductUnitCode());

        $array[$lineItemKey]['lineItems'][] = $lineItem;
        $array[$lineItemKey]['product'] = $product;
        $array[$lineItemKey]['matrixFormType'] = $this->getAvailableMatrixFormType($product, $lineItem);
    }

    /**
     * @param int $productId
     * @param string $unit
     * @return string
     */
    protected function getLineItemKey($productId, $unit)
    {
        return sprintf('%s:%s', $productId, $unit);
    }

    /**
     * @param Product $product
     * @param LineItem $lineItem
     * @return string
     */
    protected function getAvailableMatrixFormType(Product $product, LineItem $lineItem)
    {
        $type = $this->productFormAvailabilityProvider->getAvailableMatrixFormType($product);
        if ($type === Configuration::MATRIX_FORM_NONE
            || $product->getPrimaryUnitPrecision()->getProductUnitCode() !== $lineItem->getProductUnitCode()
        ) {
            return Configuration::MATRIX_FORM_NONE;
        }

        return $type;
    }
}
