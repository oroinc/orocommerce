<?php

namespace OroB2B\Bundle\ShoppingListBundle\DataProvider;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Component\Checkout\DataProvider\CheckoutDataProviderInterface;
use OroB2B\Component\Checkout\Model\DTO\EntitySummaryDTO;

class ShoppingListDataProvider implements CheckoutDataProviderInterface
{
    /** @var  FrontendProductPricesDataProvider */
    protected $frontendProductPricesDataProvider;

    /** @var  UserCurrencyProvider */
    protected $currencyProvider;

    /**
     * @param FrontendProductPricesDataProvider $frontendProductPricesDataProvider
     * @param UserCurrencyProvider $currencyProvider
     */
    public function __construct(
        FrontendProductPricesDataProvider $frontendProductPricesDataProvider,
        UserCurrencyProvider $currencyProvider
    ) {
        $this->frontendProductPricesDataProvider = $frontendProductPricesDataProvider;
        $this->currencyProvider = $currencyProvider;
    }

    /**
     * @param ShoppingList $shoppingList
     * @return EntitySummaryDTO
     */
    public function getData($shoppingList)
    {
        $shoppingListPrices = $this->frontendProductPricesDataProvider->getProductsPrices($shoppingList);
        $generalTotal = 0;
        $items = 0;
        $data = [];
        foreach ($shoppingList->getLineItems() as $lineItem) {
            $product = $lineItem->getProduct();
            $quantity = $lineItem->getQuantity();
            /** @var Price $priceEntity */
            $priceEntity = $shoppingListPrices[$lineItem->getProduct()->getId()];
            $totalPrice = clone $priceEntity;
            $totalPrice->setValue($quantity * $priceEntity->getValue());
            $data['data'][] = [
                'item' => [
                    'name' => $product->getNames()->first()->getString(),
                    'productId' => $product->getId(),
                    'item' => $lineItem->getProductSku()
                ],
                'quantity' => $quantity,
                'price' => ['itemPrice' => $priceEntity, 'total' => $totalPrice],
            ];
            $generalTotal += (float)$totalPrice->getValue();
            $items += $quantity;
        };
        $totalPrice = new Price();
        $totalPrice->setCurrency($this->currencyProvider->getUserCurrency());
        $totalPrice->setValue($generalTotal);
        $data['total'] = $totalPrice;
        $data['itemsCount'] = $items;
        $head = ['Item', 'Quantity', 'Price'];

        return new EntitySummaryDTO($head, $data);
    }

    /**
     * {@inheritDoc}
     */
    public function isEntitySupported($entity)
    {
        return $entity instanceof ShoppingList;
    }
}
