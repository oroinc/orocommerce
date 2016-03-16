<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;

class SummaryDataProvider implements DataProviderInterface
{
    /**
     * @var CheckoutLineItemsManager
     */
    protected $checkoutLineItemsManager;

    /**
     * @var LineItemSubtotalProvider
     */
    protected $lineItemsSubtotalProvider;

    /**
     * @param CheckoutLineItemsManager $CheckoutLineItemsManager
     * @param LineItemSubtotalProvider $ineItemsSubtotalProvider
     */
    public function __construct(
        CheckoutLineItemsManager $CheckoutLineItemsManager,
        LineItemSubtotalProvider $ineItemsSubtotalProvider
    ) {
        $this->checkoutLineItemsManager = $CheckoutLineItemsManager;
        $this->lineItemsSubtotalProvider = $ineItemsSubtotalProvider;
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

        $orderLineItems = $this->checkoutLineItemsManager->getData($checkout);
        $lineItemTotals = $this->getOrderLineItemsTotals($orderLineItems);

        return [
            'lineItemTotals' => $lineItemTotals,
            'lineItems' => $orderLineItems,
            'lineItemsCount' => $orderLineItems->count(),
            'totalPrice' => $this->getTotalPrice($orderLineItems)
        ];
    }

    /**
     * @param $orderLineItems
     * @return Price
     */
    protected function getTotalPrice($orderLineItems)
    {
        $order = new Order();
        $order->setLineItems($orderLineItems);
        $generalTotal = $this->lineItemsSubtotalProvider->getSubtotal($order);
        unset($order);

        $totalPrice = new Price();
        $totalPrice->setValue($generalTotal->getAmount());
        $totalPrice->setCurrency($generalTotal->getCurrency());

        return $totalPrice;
    }

    /**
     * @param Collection|OrderLineItem[] $orderLineItems
     * @return array
     */
    protected function getOrderLineItemsTotals(Collection $orderLineItems)
    {
        $lineItemTotals = [];
        foreach ($orderLineItems as $orderLineItem) {
            $lineItemTotal = new Price();
            $lineItemTotal->setValue(
                $this->lineItemsSubtotalProvider->getRowTotal($orderLineItem, $orderLineItem->getCurrency())
            );
            $lineItemTotal->setCurrency($orderLineItem->getCurrency());

            $lineItemTotals[$orderLineItem->getProductSku()] = $lineItemTotal;
        }

        return $lineItemTotals;
    }
}
