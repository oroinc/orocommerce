<?php

namespace Oro\Bundle\PaymentBundle\Provider\MethodsConfigsRule\Context\Basic;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentMethodsConfigsRuleRepository;
use Oro\Bundle\PaymentBundle\Provider\MethodsConfigsRule\Context\MethodsConfigsRulesByContextProviderInterface;
use Oro\Bundle\PaymentBundle\RuleFiltration\MethodsConfigsRulesFiltrationServiceInterface;

class BasicMethodsConfigsRulesByContextProvider implements MethodsConfigsRulesByContextProviderInterface
{
    /**
     * @var MethodsConfigsRulesFiltrationServiceInterface
     */
    private $filtrationService;

    /**
     * @var PaymentMethodsConfigsRuleRepository
     */
    private $repository;

    public function __construct(
        MethodsConfigsRulesFiltrationServiceInterface $filtrationService,
        PaymentMethodsConfigsRuleRepository $repository
    ) {
        $this->filtrationService = $filtrationService;
        $this->repository = $repository;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentMethodsConfigsRules(PaymentContextInterface $context)
    {
        if ($context->getBillingAddress()) {
            $methodsConfigsRules = $this->repository->getByDestinationAndCurrencyAndWebsite(
                $context->getBillingAddress(),
                $context->getCurrency(),
                $context->getWebsite()
            );
        } else {
            $methodsConfigsRules = $this->repository->getByCurrencyAndWebsiteWithoutDestination(
                $context->getCurrency(),
                $context->getWebsite()
            );
        }

        return $this->filtrationService->getFilteredPaymentMethodsConfigsRules(
            $methodsConfigsRules,
            $context
        );
    }
}
