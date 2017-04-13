<?php

namespace Oro\Bundle\PaymentBundle\Method\Provider;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutRepository;
use Oro\Bundle\CheckoutBundle\Factory\CheckoutPaymentContextFactory;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentMethodsConfigsRulesProviderInterface;

class PaymentMethodProvider
{
    /**
     * @var PaymentMethodProvidersRegistryInterface
     */
    private $paymentMethodRegistry;

    /**
     * @var PaymentMethodsConfigsRulesProviderInterface
     */
    private $paymentMethodsConfigsRulesProvider;

    /**
     * @var CheckoutPaymentContextFactory
     */
    private $contextFactory;

    /**
     * @var CheckoutRepository
     */
    private $checkoutRepository;

    /**
     * @param PaymentMethodProvidersRegistryInterface $paymentMethodRegistry
     * @param PaymentMethodsConfigsRulesProviderInterface $paymentMethodsConfigsRulesProvider
     * @param CheckoutPaymentContextFactory $contextFactory
     * @param CheckoutRepository $checkoutRepository
     */
    public function __construct(
        PaymentMethodProvidersRegistryInterface $paymentMethodRegistry,
        PaymentMethodsConfigsRulesProviderInterface $paymentMethodsConfigsRulesProvider,
        CheckoutPaymentContextFactory $contextFactory,
        CheckoutRepository $checkoutRepository
    ) {
        $this->paymentMethodRegistry = $paymentMethodRegistry;
        $this->paymentMethodsConfigsRulesProvider = $paymentMethodsConfigsRulesProvider;
        $this->contextFactory = $contextFactory;
        $this->checkoutRepository = $checkoutRepository;
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
     * @param PaymentTransaction $paymentTransaction
     * @return null|PaymentMethodInterface[]
     */
    public function getApplicablePaymentMethodsForTransaction(PaymentTransaction $paymentTransaction)
    {
        $transactionOptions = $paymentTransaction->getTransactionOptions();
        if (empty($transactionOptions['checkoutId'])) {
            return null;
        }
        /** @var Checkout|null $checkout */
        $checkout = $this->checkoutRepository->find($transactionOptions['checkoutId']);
        if (!$checkout) {
            return null;
        }
        $context = $this->contextFactory->create($checkout);
        if (!$context) {
            return null;
        }
        return $this->getApplicablePaymentMethods($context);
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
            $paymentMethods = array_merge($paymentMethods, $this->getPaymentMethodsForConfig($methodConfig, $context));
        }

        return $paymentMethods;
    }

    /**
     * @param PaymentMethodConfig $methodConfig
     * @param PaymentContextInterface $context
     * @return array
     */
    protected function getPaymentMethodsForConfig(PaymentMethodConfig $methodConfig, PaymentContextInterface $context)
    {
        $paymentMethods = [];
        foreach ($this->paymentMethodRegistry->getPaymentMethodProviders() as $provider) {
            $paymentMethod = $provider
                ->getPaymentMethod($methodConfig->getType());
            if ($paymentMethod && $paymentMethod->isApplicable($context)) {
                $paymentMethods[$methodConfig->getType()] = $paymentMethod;
            }
        }
        return $paymentMethods;
    }
}
