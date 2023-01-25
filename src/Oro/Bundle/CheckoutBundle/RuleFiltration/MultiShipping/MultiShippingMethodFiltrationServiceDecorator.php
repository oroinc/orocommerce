<?php

namespace Oro\Bundle\CheckoutBundle\RuleFiltration\MultiShipping;

use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\DefaultMultipleShippingMethodProvider;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;

/**
 * Filter available shipping method and remove multiple shipping methods if multi shipping is disabled.
 */
class MultiShippingMethodFiltrationServiceDecorator implements RuleFiltrationServiceInterface
{
    private RuleFiltrationServiceInterface $filtrationService;
    private DefaultMultipleShippingMethodProvider $multiShippingMethodsProvider;
    private ConfigProvider $configProvider;

    public function __construct(
        RuleFiltrationServiceInterface $filtrationService,
        DefaultMultipleShippingMethodProvider $multiShippingMethodsProvider,
        ConfigProvider $configProvider
    ) {
        $this->filtrationService = $filtrationService;
        $this->multiShippingMethodsProvider = $multiShippingMethodsProvider;
        $this->configProvider = $configProvider;
    }

    /**
     * Multi shipping methods should be available only if multi shipping functionality is enabled. Otherwise remove
     * them from available shipping methods.
     *
     * {@inheritdoc}
     */
    public function getFilteredRuleOwners(array $ruleOwners, array $context)
    {
        if (!$this->isFilterApplicable()) {
            return $this->filtrationService->getFilteredRuleOwners($ruleOwners, $context);
        }

        $multipleShippingMethodsIdentifiers = $this->multiShippingMethodsProvider->getShippingMethods();
        $filteredOwners = array_filter(
            $ruleOwners,
            fn ($ruleOwner) => !$this->isMultipleShippingMethod($ruleOwner, $multipleShippingMethodsIdentifiers)
        );

        return $this->filtrationService->getFilteredRuleOwners($filteredOwners, $context);
    }

    private function isMultipleShippingMethod($ruleOwner, array $shippingMethods): bool
    {
        if (!$ruleOwner instanceof ShippingMethodsConfigsRule) {
            return false;
        }

        $methodConfigs = $ruleOwner->getMethodConfigs();

        /** @var ShippingMethodConfig $config */
        foreach ($methodConfigs as $config) {
            if (in_array($config->getMethod(), $shippingMethods)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check cases when filter should be skipped.
     *
     * @return bool
     */
    private function isFilterApplicable(): bool
    {
        return $this->configProvider->isShippingSelectionByLineItemEnabled()
            || $this->multiShippingMethodsProvider->hasShippingMethods();
    }
}
