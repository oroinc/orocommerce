<?php

namespace Oro\Bundle\CheckoutBundle\Action;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Factory\ShippingContextProviderFactory;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig;
use Oro\Bundle\ShippingBundle\Provider\ShippingPriceProvider;
use Oro\Bundle\ShippingBundle\Provider\ShippingRulesProvider;

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
     * @var ShippingPriceProvider
     */
    protected $priceProvider;

    /**
     * @param ShippingContextProviderFactory $contextProviderFactory
     * @param ShippingRulesProvider $rulesProvider
     * @param ShippingPriceProvider $priceProvider
     */
    public function __construct(
        ShippingContextProviderFactory $contextProviderFactory,
        ShippingRulesProvider $rulesProvider,
        ShippingPriceProvider $priceProvider
    ) {
        $this->contextProviderFactory = $contextProviderFactory;
        $this->rulesProvider = $rulesProvider;
        $this->priceProvider = $priceProvider;
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
        /** @var ShippingRuleMethodConfig $config */
        $config = $rule->getMethodConfigs()->first();

        $checkout->setShippingMethod($config->getMethod());
        $checkout->setShippingMethodType($config->getTypeConfigs()->first()->getType());
        $cost = $this->priceProvider->getPrice(
            $context,
            $checkout->getShippingMethod(),
            $checkout->getShippingMethodType()
        );
        $checkout->setShippingCost($cost);
    }
}
