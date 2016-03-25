<?php

namespace OroB2B\Bundle\CheckoutBundle\Layout\DataProvider;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\Provider\LineItemSubtotalProvider;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class SummaryDataProvider extends AbstractServerRenderDataProvider
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
     * @var TotalProcessorProvider
     */
    protected $totalsProvider;

    /**
     * @var array
     */
    protected $summary = [];

    /**
     * @param CheckoutLineItemsManager $checkoutLineItemsManager
     * @param LineItemSubtotalProvider $lineItemsSubtotalProvider
     * @param TotalProcessorProvider $totalsProvider
     */
    public function __construct(
        CheckoutLineItemsManager $checkoutLineItemsManager,
        LineItemSubtotalProvider $lineItemsSubtotalProvider,
        TotalProcessorProvider $totalsProvider
    ) {
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
        $this->totalsProvider = $totalsProvider;
        $this->lineItemSubtotalProvider = $lineItemsSubtotalProvider;
    }

    /**
     * @param ContextInterface $context
     * @return ArrayCollection
     */
    public function getData(ContextInterface $context)
    {
        /** @var Checkout $checkout */
        $checkout = $context->data()->get('checkout');

        if (!array_key_exists($checkout->getId(), $this->summary)) {
            $orderLineItems = $this->checkoutLineItemsManager->getData($checkout);
            $lineItemTotals = $this->getOrderLineItemsTotals($orderLineItems);

            $this->summary[$checkout->getId()] = [
                'lineItemTotals' => $lineItemTotals,
                'lineItems' => $orderLineItems,
                'lineItemsCount' => $orderLineItems->count(),
                'subtotals' => $this->totalsProvider->getSubtotals($checkout),
                'generalTotal' => $this->totalsProvider->getTotal($checkout)
            ];
        }

        return $this->summary[$checkout->getId()];
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
                $this->lineItemSubtotalProvider->getRowTotal($orderLineItem, $orderLineItem->getCurrency())
            );
            $lineItemTotal->setCurrency($orderLineItem->getCurrency());

            $lineItemTotals[$orderLineItem->getProductSku()] = $lineItemTotal;
        }

        return $lineItemTotals;
    }
}
