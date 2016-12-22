<?php

namespace Oro\Bundle\PaymentBundle\Method\Provider;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use Oro\Bundle\PaymentBundle\Provider\PaymentMethodsConfigsRulesProviderInterface;

class PaymentMethodProvider
{
    /**
     * @var PaymentMethodRegistry
     */
    private $paymentMethodRegistry;

    /**
     * @var PaymentMethodsConfigsRulesProviderInterface
     */
    private $paymentMethodsConfigsRulesProvider;

    /**
     * @param PaymentMethodRegistry $paymentMethodRegistry
     * @param PaymentMethodsConfigsRulesProviderInterface $paymentMethodsConfigsRulesProvider
     */
    public function __construct(
        PaymentMethodRegistry $paymentMethodRegistry,
        PaymentMethodsConfigsRulesProviderInterface $paymentMethodsConfigsRulesProvider
    ) {
        $this->paymentMethodRegistry = $paymentMethodRegistry;
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
            foreach ($paymentMethodsConfigsRule->getMethodConfigs() as $methodConfig) {
                $paymentMethods[$methodConfig->getType()] = $this->paymentMethodRegistry
                    ->getPaymentMethod($methodConfig->getType());
            }
        }

        return $paymentMethods;
    }
}
