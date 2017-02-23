<?php

namespace Oro\Bundle\PaymentBundle\Provider;

use Oro\Bundle\PaymentBundle\Context\Converter\PaymentContextToRulesValueConverterInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentMethodsConfigsRuleRepository;
use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;

class BasicPaymentMethodsConfigsRulesProvider implements PaymentMethodsConfigsRulesProviderInterface
{
    /**
     * @var PaymentContextToRulesValueConverterInterface
     */
    private $paymentContextToRulesValueConverter;

    /**
     * @var PaymentMethodsConfigsRuleRepository
     */
    private $repository;

    /**
     * @var RuleFiltrationServiceInterface
     */
    private $ruleFiltrationService;

    /**
     * BasicPaymentMethodsConfigsRulesProvider constructor.
     *
     * @param PaymentContextToRulesValueConverterInterface $paymentContextToRulesValueConverter
     * @param PaymentMethodsConfigsRuleRepository $repository
     * @param RuleFiltrationServiceInterface $ruleFiltrationService
     */
    public function __construct(
        PaymentContextToRulesValueConverterInterface $paymentContextToRulesValueConverter,
        PaymentMethodsConfigsRuleRepository $repository,
        RuleFiltrationServiceInterface $ruleFiltrationService
    ) {
        $this->paymentContextToRulesValueConverter = $paymentContextToRulesValueConverter;
        $this->repository = $repository;
        $this->ruleFiltrationService = $ruleFiltrationService;
    }

    /**
     * {@inheritDoc}
     */
    public function getFilteredPaymentMethodsConfigsRegardlessDestination(PaymentContextInterface $context)
    {
        if (null === $context->getBillingAddress()) {
            $rulesConfigs = $this->repository->getByCurrency($context->getCurrency());
        } else {
            $rulesConfigs = $this->repository->getByDestinationAndCurrency(
                $context->getBillingAddress(),
                $context->getCurrency()
            );
        }

        $rulesContext = $this->paymentContextToRulesValueConverter->convert($context);

        return $this->ruleFiltrationService->getFilteredRuleOwners($rulesConfigs, $rulesContext);
    }

    /**
     * {@inheritDoc}
     */
    public function getFilteredPaymentMethodsConfigs(PaymentContextInterface $context)
    {
        $rulesConfigs = $this->getRulesConfigs($context);
        $rulesContext = $this->paymentContextToRulesValueConverter->convert($context);

        return $this->ruleFiltrationService->getFilteredRuleOwners($rulesConfigs, $rulesContext);
    }

    /**
     * @param PaymentContextInterface $context
     *
     * @return \Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule[]
     */
    private function getRulesConfigs(PaymentContextInterface $context)
    {
        if (null === $context->getBillingAddress()) {
            return $this->repository->getByCurrencyWithoutDestination($context->getCurrency());
        }

        return $this->repository->getByDestinationAndCurrency(
            $context->getBillingAddress(),
            $context->getCurrency()
        );
    }
}
