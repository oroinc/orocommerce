<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use Oro\Bundle\ShoppingListBundle\DataProvider\FrontendProductPricesDataProvider;
use Oro\Bundle\ShoppingListBundle\DataProvider\ShoppingListLineItemsDataProvider;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;

class FrontendShoppingListProductsProvider
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var string
     */
    protected $lineItemClass;

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
     * @param DoctrineHelper $doctrineHelper
     * @param FrontendProductPricesDataProvider $productPriceProvider
     * @param $shoppingListLineItemsDataProvider $shoppingListLineItemsDataProvider
     * @param ProductPriceFormatter $productPriceFormatter
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        FrontendProductPricesDataProvider $productPriceProvider,
        ShoppingListLineItemsDataProvider $shoppingListLineItemsDataProvider,
        ProductPriceFormatter $productPriceFormatter
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->productPriceProvider = $productPriceProvider;
        $this->shoppingListLineItemsDataProvider = $shoppingListLineItemsDataProvider;
        $this->productPriceFormatter = $productPriceFormatter;
    }

    /**
     * @param string $lineItemClass
     */
    public function setLineItemClass($lineItemClass)
    {
        $this->lineItemClass = $lineItemClass;
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
     * Returns array where Shopping List id is a key and array of last added product names is a value
     *
     * @param ShoppingList[] $shoppingLists
     * @param int $productCount
     *
     * @return array
     */
    public function getLastProductNamesGroupedByShoppingList($shoppingLists, $productCount)
    {
        /** @var LineItemRepository $lineItemRepository */
        $lineItemRepository = $this->doctrineHelper->getEntityRepositoryForClass($this->lineItemClass);

        return $lineItemRepository->getLastProductNamesGroupedByShoppingList($shoppingLists, $productCount);
    }
}
