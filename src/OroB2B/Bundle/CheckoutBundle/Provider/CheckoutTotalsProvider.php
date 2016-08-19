<?php

namespace OroB2B\Bundle\CheckoutBundle\Provider;

use OroB2B\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class CheckoutTotalsProvider
{
    /**
     * @var CheckoutLineItemsManager
     */
    protected $checkoutLineItemsManager;

    /**
     * @var TotalProcessorProvider
     */
    protected $totalsProvider;

    /**
     * @param CheckoutLineItemsManager $checkoutLineItemsManager
     * @param TotalProcessorProvider $totalsProvider
     */
    public function __construct(
        CheckoutLineItemsManager $checkoutLineItemsManager,
        TotalProcessorProvider $totalsProvider
    ) {
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
        $this->totalsProvider = $totalsProvider;
    }

    /**
     * @param Checkout $checkout
     * @return array
     */
    public function getTotalsArray(Checkout $checkout)
    {
        $order = new Order();
        $order->setShippingCost($checkout->getShippingCost());
        $order->setLineItems($this->checkoutLineItemsManager->getData($checkout));
        $this->totalsProvider->enableRecalculation();
        return $this->totalsProvider->getTotalWithSubtotalsAsArray($order);
    }
}
