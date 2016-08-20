<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;

class ShippingCostCalculationProvider
{
    /**
     * @var ShippingMethodRegistry
     */
    protected $registry;

    /**
     * @param ShippingMethodRegistry $registry
     * @param ShippingContextProviderFactory $shippingContextProviderFactory
     */
    public function __construct(
        ShippingMethodRegistry $registry,
        ShippingContextProviderFactory $shippingContextProviderFactory
    ) {
        $this->registry = $registry;
        $this->shippingContextProviderFactory = $shippingContextProviderFactory;
    }

    /**
     * @param Checkout $entity
     * @param ShippingRuleConfiguration $config
     * @return Price
     */
    public function calculatePrice(Checkout $entity, ShippingRuleConfiguration $config)
    {
        $method = $this->registry->getShippingMethod($config->getMethod());
        $shippingContext = $this->shippingContextProviderFactory->create($entity);

        return $method->calculatePrice($shippingContext, $config);
    }
}
