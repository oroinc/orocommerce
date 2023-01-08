<?php

namespace Oro\Bundle\CheckoutBundle\Layout\DataProvider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutTotalsProvider;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

/**
 * Provides a total of a specific checkout for layouts.
 */
class TotalsProvider
{
    private CheckoutTotalsProvider $checkoutTotalsProvider;
    private array $totals = [];

    public function __construct(CheckoutTotalsProvider $checkoutTotalsProvider)
    {
        $this->checkoutTotalsProvider = $checkoutTotalsProvider;
    }

    public function getData(Checkout $checkout): array
    {
        $checkoutId = $checkout->getId();
        if (!\array_key_exists($checkoutId, $this->totals)) {
            $totals = $this->checkoutTotalsProvider->getTotalsArray($checkout);
            foreach ($totals[TotalProcessorProvider::SUBTOTALS] as $subtotal) {
                if ('subtotal' === $subtotal['type']) {
                    $totals['subtotal'] = $subtotal;
                    break;
                }
            }
            $this->totals[$checkoutId] = $totals;
        }

        return $this->totals[$checkoutId];
    }
}
