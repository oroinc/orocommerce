<?php

namespace Oro\Bundle\CheckoutBundle\RuleFiltration\MultiShipping;

use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\DefaultMultipleShippingMethodProvider;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;

/**
 * Removes shipping rules for Multi Shipping method.
 */
class MultiShippingMethodFiltrationService implements RuleFiltrationServiceInterface
{
    private RuleFiltrationServiceInterface $filtrationService;
    private DefaultMultipleShippingMethodProvider $multiShippingMethodProvider;
    private ConfigProvider $configProvider;

    public function __construct(
        RuleFiltrationServiceInterface $filtrationService,
        DefaultMultipleShippingMethodProvider $multiShippingMethodProvider,
        ConfigProvider $configProvider
    ) {
        $this->filtrationService = $filtrationService;
        $this->multiShippingMethodProvider = $multiShippingMethodProvider;
        $this->configProvider = $configProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilteredRuleOwners(array $ruleOwners, array $context): array
    {
        if ($this->isFilterApplicable()) {
            $multipleShippingMethodsIdentifiers = $this->multiShippingMethodProvider->getShippingMethods();
            $filteredRuleOwners = [];
            foreach ($ruleOwners as $ruleOwner) {
                if (!$this->isMultipleShippingMethod($ruleOwner, $multipleShippingMethodsIdentifiers)) {
                    $filteredRuleOwners[] = $ruleOwner;
                }
            }
            $ruleOwners = $filteredRuleOwners;
        }

        return $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context);
    }

    private function isMultipleShippingMethod($ruleOwner, array $shippingMethods): bool
    {
        if ($ruleOwner instanceof ShippingMethodsConfigsRule) {
            $methodConfigs = $ruleOwner->getMethodConfigs();
            foreach ($methodConfigs as $config) {
                if (\in_array($config->getMethod(), $shippingMethods, true)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function isFilterApplicable(): bool
    {
        return
            $this->configProvider->isShippingSelectionByLineItemEnabled()
            || $this->multiShippingMethodProvider->hasShippingMethods();
    }
}
