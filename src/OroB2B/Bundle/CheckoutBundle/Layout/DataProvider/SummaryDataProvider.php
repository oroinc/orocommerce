<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\PricingBundle\Provider\UserCurrencyProvider;

class SummaryDataProvider implements DataProviderInterface
{
    /**
     * @var CheckoutLineItemsManager
     */
    protected $checkoutLineItemsManager;

    /**
     * @var UserCurrencyProvider
     */
    protected $currencyProvider;

    /**
     * @param CheckoutLineItemsManager $CheckoutLineItemsManager
     * @param UserCurrencyProvider $currencyProvider
     */
    public function __construct(
        CheckoutLineItemsManager $CheckoutLineItemsManager,
        UserCurrencyProvider $currencyProvider
    ) {
        $this->checkoutLineItemsManager = $CheckoutLineItemsManager;
        $this->currencyProvider = $currencyProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier()
    {
        throw new \BadMethodCallException('Not implemented yet');
    }

    /**
     * @param ContextInterface $context
     * @return ArrayCollection
     */
    public function getData(ContextInterface $context)
    {
        /** @var Checkout $checkout */
        $checkout = $context->data()->get('checkout');
        $lineItemTotals = [];
        $generalTotal = 0;
        $itemsCount = 0;

        $orderLineItems = $this->checkoutLineItemsManager->getData($checkout);

        foreach ($orderLineItems as $orderLineItem) {
            $quantity = $orderLineItem->getQuantity();
            $generalTotal += (float)$orderLineItem->getPrice()->getValue() * $quantity;
            $itemsCount += $quantity;

            $lineItemTotal = new Price();
            $lineItemTotal->setValue($quantity * $orderLineItem->getPrice()->getValue());
            $lineItemTotal->setCurrency($orderLineItem->getCurrency());
            $lineItemTotals[$orderLineItem->getProductSku()] = $lineItemTotal;
        }

        $totalPrice = new Price();
        $totalPrice->setValue($generalTotal);
        $totalPrice->setCurrency($this->currencyProvider->getUserCurrency());

        return [
            'lineItemTotals' => $lineItemTotals,
            'lineItems' => $orderLineItems,
            'lineItemsCount' => $itemsCount,
            'totalPrice' => $totalPrice
        ];
    }
}
