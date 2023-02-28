<?php

namespace Oro\Bundle\CheckoutBundle\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ShippingBundle\Provider\MethodsConfigsRule\Context\MethodsConfigsRulesByContextProviderInterface;

/**
 * Checks whether at least one shipping method is available for a specific checkout.
 */
class AvailableShippingMethodChecker implements AvailableShippingMethodCheckerInterface
{
    private MethodsConfigsRulesByContextProviderInterface $shippingMethodsConfigsRulesProvider;
    private CheckoutShippingContextProvider $checkoutShippingContextProvider;

    public function __construct(
        MethodsConfigsRulesByContextProviderInterface $shippingMethodsConfigsRulesProvider,
        CheckoutShippingContextProvider $checkoutShippingContextProvider
    ) {
        $this->shippingMethodsConfigsRulesProvider = $shippingMethodsConfigsRulesProvider;
        $this->checkoutShippingContextProvider = $checkoutShippingContextProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function hasAvailableShippingMethods(Checkout $checkout): bool
    {
        $shippingMethodsConfigs = $this->shippingMethodsConfigsRulesProvider->getShippingMethodsConfigsRules(
            $this->checkoutShippingContextProvider->getContext($checkout)
        );

        return \count($shippingMethodsConfigs) > 0;
    }
}
