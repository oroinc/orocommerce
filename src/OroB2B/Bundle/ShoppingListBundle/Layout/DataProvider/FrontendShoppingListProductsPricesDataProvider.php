<?php

namespace OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

use OroB2B\Bundle\ShoppingListBundle\DataProvider\FrontendProductPricesDataProvider;

class FrontendShoppingListProductsPricesDataProvider implements DataProviderInterface
{
    /**
     * @var FrontendProductPricesDataProvider
     */
    protected $productPriceProvider;

    /**
     * @param FrontendProductPricesDataProvider $productPriceProvider
     */
    public function __construct(
        FrontendProductPricesDataProvider $productPriceProvider
    ) {
        $this->productPriceProvider = $productPriceProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        throw new \BadMethodCallException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        $shoppingList = $context->data()->get('shoppingList');
        if (!$shoppingList) {
            return null;
        }

        return $this->productPriceProvider->getProductsPrices($shoppingList);
    }
}
