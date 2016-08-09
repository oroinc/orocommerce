<?php

namespace OroB2B\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingContextProvider;

class ShippingCostCalculationProvider
{
    /**
     * @var ShippingMethodRegistry
     */
    protected $registry;

    /**
     * @param ShippingMethodRegistry $registry
     */
    public function __construct(ShippingMethodRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param Checkout $entity
     * @param ShippingRuleConfiguration $config
     * @return Price
     */
    public function calculatePrice(Checkout $entity, ShippingRuleConfiguration $config)
    {
        $method = $this->registry->getShippingMethod($config->getMethod());
        $context = [
            'checkout' => $entity,
            'billingAddress' => $entity->getBillingAddress(),
            'currency' => $entity->getCurrency(),
        ];
        $sourceEntity = $entity->getSourceEntity();
        if (!empty($sourceEntity)) {
            $context['line_items'] = $sourceEntity->getLineItems();
        }
        $shippingContext = new ShippingContextProvider($context);
        $cost = $method->calculatePrice($shippingContext, $config);
        
        return $cost;
    }
}
