<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
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

    /** @var ConfigManager */
    private $configManager;

    /**
     * @param MatrixGridOrderFormProvider $matrixGridOrderFormProvider
     * @param ProductFormAvailabilityProvider $productFormAvailabilityProvider
     * @param ConfigManager $configManager
     */
    public function __construct(
        MatrixGridOrderFormProvider $matrixGridOrderFormProvider,
        ProductFormAvailabilityProvider $productFormAvailabilityProvider,
        ConfigManager $configManager
    ) {
        $this->matrixGridOrderFormProvider = $matrixGridOrderFormProvider;
        $this->productFormAvailabilityProvider = $productFormAvailabilityProvider;
        $this->configManager = $configManager;
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

            if (!isset($sortedLineItems[$product->getId()])) {
                if ($this->getMatrixFormConfig() === Configuration::MATRIX_FORM_ON_SHOPPING_LIST_INLINE
                    && $this->productFormAvailabilityProvider->isMatrixFormAvailable($product)
                ) {
                    // Add matrix form view to line item data for applicable configurable products
                    $sortedLineItems[$product->getId()]['form'] =
                        $this->matrixGridOrderFormProvider->getMatrixOrderFormView($product, $shoppingList);
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
        foreach ($productVariants as $productVariant) {
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
        $array[$product->getId()]['lineItems'][] = $lineItem;
        $array[$product->getId()]['product'] = $product;
    }

    /**
     * @return string
     */
    protected function getMatrixFormConfig()
    {
        return $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::MATRIX_FORM_ON_SHOPPING_LIST));
    }
}
