<?php

namespace OroB2B\Bundle\ShoppingListBundle\DataProvider;

use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Component\Checkout\DataProvider\CheckoutDataProviderInterface;

class CheckoutLineItemDataProvider implements CheckoutDataProviderInterface
{
    /**
     * @var FrontendProductPricesDataProvider
     */
    protected $frontendProductPricesDataProvider;

    /**
     * @var UserCurrencyProvider
     */
    protected $currencyProvider;

    /**
     * @param FrontendProductPricesDataProvider $frontendProductPricesDataProvider
     */
    public function __construct(FrontendProductPricesDataProvider $frontendProductPricesDataProvider)
    {
        $this->frontendProductPricesDataProvider = $frontendProductPricesDataProvider;
    }

    /**
     * @param ShoppingList $shoppingList
     * @return array
     */
    public function getData($shoppingList)
    {
        $shoppingListPrices = $this->frontendProductPricesDataProvider->getProductsPrices($shoppingList);

        $data = [];
        foreach ($shoppingList->getLineItems() as $lineItem) {
            $data[] = [
                'product' => $lineItem->getProduct(),
                'productSku' => $lineItem->getProductSku(),
                'quantity' => $lineItem->getQuantity(),
                'productUnit' => $lineItem->getProductUnit(),
                'productUnitCode' => $lineItem->getProductUnitCode(),
                'price' => $shoppingListPrices[$lineItem->getProduct()->getId()],
            ];
        }

        return $data;
    }

    /**
     * {@inheritDoc}
     */
    public function isTransformDataSupported($transformData)
    {
        return $transformData instanceof ShoppingList;
    }
}
