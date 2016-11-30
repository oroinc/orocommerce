<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\ShoppingListBundle\DataProvider\FrontendProductPricesDataProvider;
use Oro\Bundle\ShoppingListBundle\DataProvider\ShoppingListLineItemsDataProvider;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class FrontendShoppingListProductsProvider
{
    /**
     * @var LineItemRepository
     */
    protected $lineItemRepository;

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
     * @param LineItemRepository $lineItemRepository
     * @param FrontendProductPricesDataProvider $productPriceProvider
     * @param $shoppingListLineItemsDataProvider $shoppingListLineItemsDataProvider
     * @param ProductPriceFormatter $productPriceFormatter
     */
    public function __construct(
        LineItemRepository $lineItemRepository,
        FrontendProductPricesDataProvider $productPriceProvider,
        ShoppingListLineItemsDataProvider $shoppingListLineItemsDataProvider,
        ProductPriceFormatter $productPriceFormatter
    ) {
        $this->lineItemRepository = $lineItemRepository;
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

    /**
     * Returns array where Shopping List id is a key and array of last added products is a value
     *
     * Example:
     * [
     *   74 => [
     *     ['name' => '220 Lumen Rechargeable Headlamp'],
     *     ['name' => 'Credit Card Pin Pad Reader']
     *   ]
     * ]
     *
     * @param ShoppingList[] $shoppingLists
     * @param int $productCount
     *
     * @return array
     */
    public function getLastProductsGroupedByShoppingList(array $shoppingLists, $productCount)
    {
        return $this->lineItemRepository->getLastProductsGroupedByShoppingList($shoppingLists, $productCount);
    }
}
