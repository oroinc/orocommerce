<?php

namespace OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\ShoppingListBundle\DataProvider\FrontendProductPricesDataProvider;
use OroB2B\Bundle\ShoppingListBundle\DataProvider\ShoppingListLineItemsDataProvider;

class FrontendShoppingListProductsMatchedPriceProvider extends AbstractServerRenderDataProvider
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
     * @param FrontendProductPricesDataProvider $productPriceProvider
     * @param ShoppingListLineItemsDataProvider $shoppingListLineItemsDataProvider
     */
    public function __construct(
        FrontendProductPricesDataProvider $productPriceProvider,
        ShoppingListLineItemsDataProvider $shoppingListLineItemsDataProvider
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->shoppingListLineItemsDataProvider = $shoppingListLineItemsDataProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        $shoppingList = $context->data()->get('entity');
        if (!$shoppingList) {
            return null;
        }
        $lineItems = $this->shoppingListLineItemsDataProvider->getShoppingListLineItems($shoppingList);
        return $this->productPriceProvider->getProductsMatchedPrice($lineItems);
    }
}
