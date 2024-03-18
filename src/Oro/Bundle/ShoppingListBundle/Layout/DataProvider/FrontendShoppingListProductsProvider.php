<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\PricingBundle\Model\ProductLineItemPrice\ProductLineItemPrice;
use Oro\Bundle\PricingBundle\Provider\FrontendProductPricesDataProvider;
use Oro\Bundle\PricingBundle\Provider\ProductLineItemPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductKitItemLineItemsAwareInterface;
use Oro\Bundle\ShoppingListBundle\DataProvider\ShoppingListLineItemsDataProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

/**
 * Provides products and prices in relation to shopping list
 */
class FrontendShoppingListProductsProvider
{
    private LineItemRepository $lineItemRepository;

    private FrontendProductPricesDataProvider $productPriceProvider;

    private ShoppingListLineItemsDataProvider $shoppingListLineItemsDataProvider;

    private ProductPriceFormatter $productPriceFormatter;

    private ProductLineItemPriceProviderInterface $productLineItemPriceProvider;

    public function __construct(
        LineItemRepository $lineItemRepository,
        FrontendProductPricesDataProvider $productPriceProvider,
        ShoppingListLineItemsDataProvider $shoppingListLineItemsDataProvider,
        ProductPriceFormatter $productPriceFormatter,
        ProductLineItemPriceProviderInterface $productLineItemPriceProvider
    ) {
        $this->lineItemRepository = $lineItemRepository;
        $this->productPriceProvider = $productPriceProvider;
        $this->shoppingListLineItemsDataProvider = $shoppingListLineItemsDataProvider;
        $this->productPriceFormatter = $productPriceFormatter;
        $this->productLineItemPriceProvider = $productLineItemPriceProvider;
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
        $productPrices = $this->productPriceProvider->getAllPricesForLineItems($lineItems);

        return $this->productPriceFormatter->formatProducts($productPrices);
    }

    /**
     * Component added back for theme layout BC from version 5.1
     *
     * @param ShoppingList|null $shoppingList
     *
     * @return array|null
     *  [
     *      10 => [ // simple product id
     *          'each' => Price $price // price keyed with unit code
     *      ],
     *      20 => [ // product kit id
     *          'each' => [ // unit code
     *              'sample_checksum' => Price $price // price keyed with line item checksum
     *              // ...
     *          ],
     *      ],
     *      // ...
     *  ]
     */
    public function getMatchedPrice(ShoppingList $shoppingList = null)
    {
        if (!$shoppingList) {
            return null;
        }

        if ($this->productLineItemPriceProvider !== null) {
            return $this->getMatchedPrices([$shoppingList])[$shoppingList->getId()] ?? [];
        }

        // BC fallback.
        $lineItems = $this->shoppingListLineItemsDataProvider->getShoppingListLineItems($shoppingList);

        return $this->productPriceProvider->getProductsMatchedPrice($lineItems);
    }

    /**
     * Component added back for theme layout BC from version 5.1
     *
     * @param ShoppingList[] $shoppingLists
     * @return array
     *  [
     *      42 => [ // shopping list id
     *          10 => [ // simple product id
     *              'each' => Price $price // price keyed with unit code
     *          ],
     *          20 => [ // product kit id
     *              'each' => [ // unit code
     *                  'sample_checksum' => Price $price // price keyed with line item checksum
     *              ],
     *          ],
     *          // ...
     *      ],
     *      // ...
     *  ]
     */
    public function getMatchedPrices(array $shoppingLists = [])
    {
        if (!$shoppingLists) {
            return [];
        }

        if ($this->productLineItemPriceProvider !== null) {
            $matchedPrices = [];
            $productLineItemPricesByShoppingList = $this->getProductLineItemPricesForShoppingLists($shoppingLists);
            foreach ($productLineItemPricesByShoppingList as $shoppingListId => $productLineItemPrices) {
                $matchedPrices[$shoppingListId] = [];
                foreach ($productLineItemPrices as $productLineItemPrice) {
                    $lineItem = $productLineItemPrice->getLineItem();
                    $product = $lineItem->getProduct();
                    $productUnitCode = $lineItem->getProductUnitCode();
                    $productId = $product->getId();
                    if ($lineItem instanceof ProductKitItemLineItemsAwareInterface && $product->isKit()) {
                        $matchedPrices[$shoppingListId][$productId][$productUnitCode][$lineItem->getChecksum()] =
                            $productLineItemPrice->getPrice();
                    } else {
                        $matchedPrices[$shoppingListId][$productId][$productUnitCode] =
                            $productLineItemPrice->getPrice();
                    }
                }
            }

            return $matchedPrices;
        }

        // BC fallback.
        $prices = [];
        foreach ($shoppingLists as $shoppingList) {
            $prices[$shoppingList->getId()] = $this->getMatchedPrice($shoppingList);
        }

        return $prices;
    }

    /**
     * @param array<ShoppingList> $shoppingLists
     *
     * @return array<int,array<ProductLineItemPrice>>
     */
    public function getProductLineItemPricesForShoppingLists(array $shoppingLists = []): array
    {
        if (!$shoppingLists) {
            return [];
        }

        $productLineItemPrices = [];
        foreach ($shoppingLists as $shoppingList) {
            $lineItems = $this->shoppingListLineItemsDataProvider->getShoppingListLineItems($shoppingList);
            if (!$lineItems) {
                $productLineItemPrices[$shoppingList->getId()] = [];
                continue;
            }

            $productLineItemPrices[$shoppingList->getId()] = $this->productLineItemPriceProvider
                ->getProductLineItemsPrices($lineItems);
        }

        return $productLineItemPrices;
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
     * @param Localization|null $localization
     *
     * @return array
     */
    public function getLastProductsGroupedByShoppingList(
        array $shoppingLists,
        $productCount,
        Localization $localization = null
    ) {
        return $this->lineItemRepository->getLastProductsGroupedByShoppingList(
            $shoppingLists,
            $productCount,
            $localization
        );
    }

    /**
     * @param ShoppingList $shoppingList
     * @return Product[]
     */
    public function getShoppingListProducts(ShoppingList $shoppingList): array
    {
        $lineItems = $this->shoppingListLineItemsDataProvider->getShoppingListLineItems($shoppingList);

        return array_map(fn (LineItem $lineItem) => $lineItem->getProduct(), $lineItems);
    }
}
