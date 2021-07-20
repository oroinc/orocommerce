<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutToOrderConverter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class CheckoutTotalsProvider
{
    /**
     * @var CheckoutToOrderConverter
     */
    protected $checkoutToOrderConverter;

    /**
     * @var TotalProcessorProvider
     */
    protected $totalsProvider;

    /**
     * @var CheckoutShippingMethodsProviderInterface
     */
    protected $checkoutShippingMethodsProvider;

    public function __construct(
        CheckoutToOrderConverter $checkoutToOrderConverter,
        TotalProcessorProvider $totalsProvider,
        CheckoutShippingMethodsProviderInterface $checkoutShippingMethodsProvider
    ) {
        $this->checkoutToOrderConverter = $checkoutToOrderConverter;
        $this->totalsProvider = $totalsProvider;
        $this->checkoutShippingMethodsProvider = $checkoutShippingMethodsProvider;
    }

    /**
     * @param Checkout $checkout
     *
     * @return array
     */
    public function getTotalsArray(Checkout $checkout)
    {
        $checkout->setShippingCost($this->checkoutShippingMethodsProvider->getPrice($checkout));
        $order = $this->checkoutToOrderConverter->getOrder($checkout);
        $this->totalsProvider->enableRecalculation();

        return $this->totalsProvider->getTotalWithSubtotalsAsArray($order);
    }
}
