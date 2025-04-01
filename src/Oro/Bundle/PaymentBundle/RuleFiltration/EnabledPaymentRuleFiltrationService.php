<?php

namespace Oro\Bundle\PaymentBundle\RuleFiltration;

use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

/**
 * Filters out disabled rule methods.
 */
class EnabledPaymentRuleFiltrationService implements RuleFiltrationServiceInterface
{
    public function __construct(
        private PaymentMethodProviderInterface $paymentMethodProvider,
        private RuleFiltrationServiceInterface $baseFiltrationService
    ) {
    }

    public function getFilteredRuleOwners(array $ruleOwners, array $context): array
    {
        $filteredRuleOwners = $this->getEnabledPaymentRuleOwners($ruleOwners);

        return $this->baseFiltrationService->getFilteredRuleOwners($filteredRuleOwners, $context);
    }

    private function getEnabledPaymentRuleOwners(array $ruleOwners): array
    {
        $enabledRuleOwners = [];
        foreach ($ruleOwners as $ruleOwner) {
            if (!$ruleOwner instanceof PaymentMethodsConfigsRule) {
                $enabledRuleOwners[] = $ruleOwner;

                continue;
            }

            if (!empty($this->getPaymentMethodsForConfigsRule($ruleOwner))) {
                $enabledRuleOwners[] = $ruleOwner;
            }
        }

        return $enabledRuleOwners;
    }

    private function getPaymentMethodsForConfigsRule(PaymentMethodsConfigsRule $paymentMethodsConfigsRule): array
    {
        $paymentMethods = [];
        foreach ($paymentMethodsConfigsRule->getMethodConfigs() as $methodConfig) {
            $paymentMethod = $this->getPaymentMethodForConfig($methodConfig);
            if ($paymentMethod) {
                $paymentMethods[$methodConfig->getType()] = $paymentMethod;
            }
        }

        return $paymentMethods;
    }

    private function getPaymentMethodForConfig(PaymentMethodConfig $methodConfig): ?PaymentMethodInterface
    {
        $identifier = $methodConfig->getType();
        if ($this->paymentMethodProvider->hasPaymentMethod($identifier)) {
            return $this->paymentMethodProvider->getPaymentMethod($identifier);
        }

        return null;
    }
}
