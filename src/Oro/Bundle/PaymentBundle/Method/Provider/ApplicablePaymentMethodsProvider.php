<?php

namespace Oro\Bundle\PaymentBundle\Method\Provider;

use Oro\Bundle\CacheBundle\Provider\MemoryCacheProviderAwareTrait;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Provider\MethodsConfigsRule\Context\MethodsConfigsRulesByContextProviderInterface;

/**
 * Returns applicable payment method for given payment context.
 */
class ApplicablePaymentMethodsProvider
{
    use MemoryCacheProviderAwareTrait;

    /**
     * @var PaymentMethodProviderInterface
     */
    private $paymentMethodProvider;

    /**
     * @var MethodsConfigsRulesByContextProviderInterface
     */
    private $paymentMethodsConfigsRulesProvider;

    public function __construct(
        PaymentMethodProviderInterface $paymentMethodProvider,
        MethodsConfigsRulesByContextProviderInterface $paymentMethodsConfigsRulesProvider
    ) {
        $this->paymentMethodProvider = $paymentMethodProvider;
        $this->paymentMethodsConfigsRulesProvider = $paymentMethodsConfigsRulesProvider;
    }

    /**
     * @param PaymentContextInterface $context
     *
     * @return PaymentMethodInterface[]
     */
    public function getApplicablePaymentMethods(PaymentContextInterface $context): array
    {
        return $this->getMemoryCacheProvider()->get(
            ['payment_context' => $context],
            function () use ($context) {
                return $this->getActualApplicablePaymentMethods($context);
            }
        );
    }

    protected function getActualApplicablePaymentMethods(PaymentContextInterface $context): array
    {
        $paymentMethodsConfigsRules = $this->paymentMethodsConfigsRulesProvider
            ->getPaymentMethodsConfigsRules($context);

        $paymentMethods = [[]];
        foreach ($paymentMethodsConfigsRules as $paymentMethodsConfigsRule) {
            $paymentMethods[] = $this->getPaymentMethodsForConfigsRule($paymentMethodsConfigsRule, $context);
        }

        return array_merge(...$paymentMethods);
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
