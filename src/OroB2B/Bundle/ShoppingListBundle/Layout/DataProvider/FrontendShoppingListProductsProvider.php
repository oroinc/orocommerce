<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\ShoppingListBundle\DataProvider\FrontendProductPricesDataProvider;
use Oro\Bundle\ShoppingListBundle\DataProvider\ShoppingListLineItemsDataProvider;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class FrontendShoppingListProductsProvider
{
    /**
     * @var FrontendProductPricesDataProvider
     */
    protected $productPriceProvider;

    /**
     * @var ShoppingListLineItemsDataProvider
     */
    protected $shoppingListLineItemsDataProvider;

    /**
     * @var ProductPriceFormatter
     */
    protected $productPriceFormatter;

    /**
     * @param FrontendProductPricesDataProvider $productPriceProvider
     * @param $shoppingListLineItemsDataProvider $shoppingListLineItemsDataProvider
     * @param ProductPriceFormatter $productPriceFormatter
     */
    public function __construct(
        FrontendProductPricesDataProvider $productPriceProvider,
        ShoppingListLineItemsDataProvider $shoppingListLineItemsDataProvider,
        ProductPriceFormatter $productPriceFormatter
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->shoppingListLineItemsDataProvider = $shoppingListLineItemsDataProvider;
        $this->productPriceFormatter = $productPriceFormatter;
    }

    /**
     * @param ShoppingList|null $shoppingList
     *
     * @return array|null
     */
    public function getAllPrices(ShoppingList $shoppingList = null)
    {
        if (!$shoppingList) {
            return null;
        }

        $lineItems = $this->shoppingListLineItemsDataProvider->getShoppingListLineItems($shoppingList);
        $productPrices = $this->productPriceProvider->getProductsAllPrices($lineItems);

        return $this->productPriceFormatter->formatProducts($productPrices);
    }

    /**
     * @param ShoppingList|null $shoppingList
     *
     * @return array|null
     */
    public function getMatchedPrice(ShoppingList $shoppingList = null)
    {
        if (!$shoppingList) {
            return null;
        }
        
        $lineItems = $this->shoppingListLineItemsDataProvider->getShoppingListLineItems($shoppingList);
        
        return $this->productPriceProvider->getProductsMatchedPrice($lineItems);
    }
}
