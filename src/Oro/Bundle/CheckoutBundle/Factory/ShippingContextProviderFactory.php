<?php

namespace Oro\Bundle\CheckoutBundle\Factory;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ShippingBundle\Provider\ShippingContextAwareInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingContextProvider;

class ShippingContextProviderFactory
{
    /**
     * @param Checkout $checkout
     * @return ShippingContextAwareInterface
     */
    public function create(Checkout $checkout)
    {
        $context = [
            'checkout' => $checkout,
            'billingAddress' => $checkout->getBillingAddress(),
            'shippingAddress' => $checkout->getShippingAddress(),
            'currency' => $checkout->getCurrency(),
        ];
        $sourceEntity = $checkout->getSourceEntity();
        // TODO: refactor durring BB-2812
        if (!empty($sourceEntity)) {
            $context['line_items'] = $sourceEntity->getLineItems();
        }
        return new ShippingContextProvider($context);
    }
}
