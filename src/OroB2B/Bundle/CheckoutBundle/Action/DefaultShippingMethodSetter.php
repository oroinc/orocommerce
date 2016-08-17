<?php

namespace OroB2B\Bundle\CheckoutBundle\Action;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Provider\ShippingCostCalculationProvider;
use OroB2B\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory;
use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;
use OroB2B\Bundle\ShippingBundle\Provider\ShippingRulesProvider;

class DefaultShippingMethodSetter
{
    /**
     * @var ShippingContextProviderFactory
     */
    protected $contextProviderFactory;

    /**
     * @var ShippingRulesProvider
     */
    protected $rulesProvider;

    /**
     * @var ShippingCostCalculationProvider
     */
    protected $costCalculationProvider;

    /**
     * @param ShippingContextProviderFactory $contextProviderFactory
     * @param ShippingRulesProvider $rulesProvider
     * @param ShippingCostCalculationProvider $costCalculationProvider
     */
    public function __construct(
        ShippingContextProviderFactory $contextProviderFactory,
        ShippingRulesProvider $rulesProvider,
        ShippingCostCalculationProvider $costCalculationProvider
    ) {
        $this->contextProviderFactory = $contextProviderFactory;
        $this->rulesProvider = $rulesProvider;
        $this->costCalculationProvider = $costCalculationProvider;
    }

    /**
     * @param Checkout $checkout
     */
    public function setDefaultShippingMethod(Checkout $checkout)
    {
        if ($checkout->getShippingMethod()) {
            return;
        }
        $context = $this->contextProviderFactory->create($checkout);
        $rules = $this->rulesProvider->getApplicableShippingRules($context);
        if (count($rules) === 0) {
            return;
        }
        /** @var ShippingRule $rule */
        $rule = reset($rules);
        $config = $rule->getConfigurations()->first();
        $checkout->setShippingMethod($config->getMethod());
        $checkout->setShippingMethodType($config->getType());
        $cost = $this->costCalculationProvider->calculatePrice($checkout, $config);
        $checkout->setShippingCost($cost);
    }
}
