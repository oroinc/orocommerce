<?php

namespace OroB2B\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\CheckoutBundle\Entity\BaseCheckout;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextProvider;

class ShippingCostCalculationProvider
{
    protected $registry;
    
    public function __construct(ShippingMethodRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @return ShippingMethodRegistry
     */
    public function getShippingMethodRegistry()
    {
        return $this->registry;
    }

    /**
     * @param BaseCheckout $checkout
     * @param ShippingRuleConfiguration $config
     * @return Price
     */
    public function calculatePrice(BaseCheckout $checkout, ShippingRuleConfiguration $config)
    {
        $method = $this->registry->getShippingMethod($config->getMethod());
        $context = [
            'checkout' => $checkout,
            'currency' => $checkout->getCurrency(),
            'line_items' => $checkout->getSourceEntity()->getLineItems(),
        ];
        $shippingContext = new ShippingContextProvider($context);
        $cost = $method->calculatePrice($shippingContext, $config);
        
        return $cost;
    }
}
