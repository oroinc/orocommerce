<?php

namespace OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\ProductBundle\Layout\DataProvider\FeaturedProductsProvider;

class FeaturedProductsShoppingListsProvider extends AbstractServerRenderDataProvider
{
    /**
     * @var FrontendShoppingListProductUnitsQuantityDataProvider
     */
    protected $productsShoppingListsProvider;

    /**
     * @var FeaturedProductsProvider
     */
    protected $featuredProductsProvider;

    /**
     * @param FrontendShoppingListProductUnitsQuantityDataProvider $productsShoppingListsProvider
     * @param FeaturedProductsProvider $productsShoppingListsProvider
     */
    public function __construct(
        FrontendShoppingListProductUnitsQuantityDataProvider $productsShoppingListsProvider,
        FeaturedProductsProvider $featuredProductsProvider
    ) {
        $this->productsShoppingListsProvider = $productsShoppingListsProvider;
        $this->featuredProductsProvider = $featuredProductsProvider;
    }

    /**
     * @inheritdoc
     */
    public function getData(ContextInterface $context)
    {
        $products = $this->featuredProductsProvider->getData($context);
        return $this->productsShoppingListsProvider->getProductsShoppingLists($products);
    }
}
