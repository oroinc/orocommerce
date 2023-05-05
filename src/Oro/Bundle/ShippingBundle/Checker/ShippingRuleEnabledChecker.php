<?php

namespace Oro\Bundle\ShippingBundle\Checker;

use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;

/**
 * The default implementation of the service to check whether a shipping rule can be enabled or not.
 */
class ShippingRuleEnabledChecker implements ShippingRuleEnabledCheckerInterface
{
    private ShippingMethodEnabledByIdentifierCheckerInterface $methodEnabledChecker;

    public function __construct(ShippingMethodEnabledByIdentifierCheckerInterface $methodEnabledChecker)
    {
        $this->methodEnabledChecker = $methodEnabledChecker;
    }

    /**
     * {@inheritDoc}
     */
    public function canBeEnabled(ShippingMethodsConfigsRule $rule): bool
    {
        $configs = $rule->getMethodConfigs();
        foreach ($configs as $config) {
            if ($this->methodEnabledChecker->isEnabled($config->getMethod())) {
                return true;
            }
        }

        return false;
    }
}
