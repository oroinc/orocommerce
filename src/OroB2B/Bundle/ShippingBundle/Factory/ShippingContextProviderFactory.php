<?php

namespace OroB2B\Bundle\ShippingBundle\Factory;

use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextAwareInterface;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextProvider;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShippingContextProviderFactory
{

    /**
     * @param CheckoutInterface $checkout
     * @return ShippingContextAwareInterface
     */
    public function create(CheckoutInterface $checkout)
    {
        $context = [
            'checkout' => $checkout,
            'billingAddress' => $checkout->getBillingAddress(),
            'currency' => $checkout->getCurrency(),
        ];
        /** @var ShoppingList $sourceEntity */
        $sourceEntity = $checkout->getSourceEntity();
        if (!empty($sourceEntity)) {
            $context['line_items'] = $sourceEntity->getLineItems();
        }
        return new ShippingContextProvider($context);
    }

}
