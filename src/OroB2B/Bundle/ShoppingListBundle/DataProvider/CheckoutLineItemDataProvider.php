<?php

namespace OroB2B\Bundle\ShoppingListBundle\DataProvider;

use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Component\Checkout\DataProvider\AbstractCheckoutProvider;

class CheckoutLineItemDataProvider extends AbstractCheckoutProvider
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
     * {@inheritDoc}
     */
    public function isEntitySupported($entity)
    {
        return $entity instanceof ShoppingList;
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareData($entity, $additionalData)
    {
        $shoppingListPrices = $this->frontendProductPricesDataProvider->getProductsPrices($entity);

        $data = [];
        foreach ($entity->getLineItems() as $lineItem) {
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
}
