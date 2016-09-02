<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

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
