<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutToOrderConverter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

/**
 * Provides a total for a specific checkout.
 */
class CheckoutTotalsProvider
{
    private CheckoutToOrderConverter $checkoutToOrderConverter;
    private TotalProcessorProvider $totalsProvider;
    private CheckoutShippingMethodsProviderInterface $checkoutShippingMethodsProvider;

    public function __construct(
        CheckoutToOrderConverter $checkoutToOrderConverter,
        TotalProcessorProvider $totalsProvider,
        CheckoutShippingMethodsProviderInterface $checkoutShippingMethodsProvider
    ) {
        $this->checkoutToOrderConverter = $checkoutToOrderConverter;
        $this->totalsProvider = $totalsProvider;
        $this->checkoutShippingMethodsProvider = $checkoutShippingMethodsProvider;
    }

    public function getTotalsArray(Checkout $checkout): array
    {
        $checkout->setShippingCost($this->checkoutShippingMethodsProvider->getPrice($checkout));
        $order = $this->checkoutToOrderConverter->getOrder($checkout);
        $this->totalsProvider->enableRecalculation();

        return $this->totalsProvider->getTotalWithSubtotalsAsArray($order);
    }
}
