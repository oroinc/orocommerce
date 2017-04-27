<?php

namespace Oro\Bundle\PaymentBundle\Method\Provider;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentMethodsConfigsRulesProviderInterface;

class PaymentMethodProvider
{
    /**
     * @var PaymentMethodProviderInterface
     */
    private $paymentMethodProvider;

    /**
     * @var PaymentMethodsConfigsRulesProviderInterface
     */
    private $paymentMethodsConfigsRulesProvider;

    /**
     * @param PaymentMethodProviderInterface $paymentMethodProvider
     * @param PaymentMethodsConfigsRulesProviderInterface $paymentMethodsConfigsRulesProvider
     */
    public function __construct(
        PaymentMethodProviderInterface $paymentMethodProvider,
        PaymentMethodsConfigsRulesProviderInterface $paymentMethodsConfigsRulesProvider
    ) {
        $this->paymentMethodProvider = $paymentMethodProvider;
        $this->paymentMethodsConfigsRulesProvider = $paymentMethodsConfigsRulesProvider;
    }

    /**
     * @param PaymentContextInterface $context
     *
     * @return PaymentMethodInterface[]
     */
    public function getApplicablePaymentMethods(PaymentContextInterface $context)
    {
        $paymentMethodsConfigsRules = $this->paymentMethodsConfigsRulesProvider
            ->getFilteredPaymentMethodsConfigs($context);

        $paymentMethods = [];

        foreach ($paymentMethodsConfigsRules as $paymentMethodsConfigsRule) {
            $paymentMethods = array_merge(
                $paymentMethods,
                $this->getPaymentMethodsForConfigsRule($paymentMethodsConfigsRule, $context)
            );
        }

        return $paymentMethods;
    }

    /**
     * @param PaymentMethodsConfigsRule $paymentMethodsConfigsRule
     * @param PaymentContextInterface $context
     * @return array
     */
    protected function getPaymentMethodsForConfigsRule(
        PaymentMethodsConfigsRule $paymentMethodsConfigsRule,
        PaymentContextInterface $context
    ) {
        $paymentMethods = [];
        foreach ($paymentMethodsConfigsRule->getMethodConfigs() as $methodConfig) {
            $paymentMethod = $this->getPaymentMethodForConfig($methodConfig, $context);
            if ($paymentMethod) {
                $paymentMethods[$methodConfig->getType()] = $paymentMethod;
            }
        }

        return $paymentMethods;
    }

    /**
     * @param PaymentMethodConfig $methodConfig
     * @param PaymentContextInterface $context
     * @return PaymentMethodInterface|null
     */
    protected function getPaymentMethodForConfig(PaymentMethodConfig $methodConfig, PaymentContextInterface $context)
    {
        $identifier = $methodConfig->getType();
        if ($this->paymentMethodProvider->hasPaymentMethod($identifier)) {
            $paymentMethod = $this->paymentMethodProvider->getPaymentMethod($identifier);

            if ($paymentMethod->isApplicable($context)) {
                return $paymentMethod;
            }
        }

        return null;
    }
}
