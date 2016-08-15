<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;

class LineItemsWithTotalsProvider
{
    /**
     * @var CheckoutLineItemsManager
     */
    protected $checkoutLineItemsManager;

    /**
     * @var LineItemSubtotalProvider
     */
    protected $lineItemSubtotalProvider;

    /**
     * @var array
     */
    protected $lineItems = [];

    /**
     * @param CheckoutLineItemsManager $checkoutLineItemsManager
     * @param LineItemSubtotalProvider $lineItemsSubtotalProvider
     */
    public function __construct(
        CheckoutLineItemsManager $checkoutLineItemsManager,
        LineItemSubtotalProvider $lineItemsSubtotalProvider
    ) {
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
        $this->lineItemSubtotalProvider = $lineItemsSubtotalProvider;
    }

    /**
     * @param Checkout $checkout
     * @return ArrayCollection
     */
    public function getData(Checkout $checkout)
    {
        if (!array_key_exists($checkout->getId(), $this->lineItems)) {
            $lineItems = $this->checkoutLineItemsManager->getData($checkout);
            $this->lineItems[$checkout->getId()] = $this->getOrderLineItemsTotals($lineItems);
        }

        return $this->lineItems[$checkout->getId()];
    }

    /**
     * @param Collection|OrderLineItem[] $orderLineItems
     * @return \SplObjectStorage
     */
    protected function getOrderLineItemsTotals(Collection $orderLineItems)
    {
        $lineItemTotals = new \SplObjectStorage();
        foreach ($orderLineItems as $orderLineItem) {
            $lineItemTotal = new Price();
            $lineItemTotal->setValue(
                $this->lineItemSubtotalProvider->getRowTotal($orderLineItem, $orderLineItem->getCurrency())
            );
            $lineItemTotal->setCurrency($orderLineItem->getCurrency());

            $lineItemTotals->attach($orderLineItem, ['total' => $lineItemTotal]);
        }

        return $lineItemTotals;
    }
}
