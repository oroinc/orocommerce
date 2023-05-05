<?php

namespace Oro\Bundle\PaymentBundle\Provider\MethodsConfigsRule\Context\Basic;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodsConfigsRule;
use Oro\Bundle\PaymentBundle\Entity\Repository\PaymentMethodsConfigsRuleRepository;
use Oro\Bundle\PaymentBundle\Provider\MethodsConfigsRule\Context\MethodsConfigsRulesByContextProviderInterface;
use Oro\Bundle\PaymentBundle\RuleFiltration\MethodsConfigsRulesFiltrationServiceInterface;

/**
 * Provides payment method config rules.
 */
class BasicMethodsConfigsRulesByContextProvider implements MethodsConfigsRulesByContextProviderInterface
{
    private MethodsConfigsRulesFiltrationServiceInterface $filtrationService;
    private ManagerRegistry $doctrine;

    public function __construct(
        MethodsConfigsRulesFiltrationServiceInterface $filtrationService,
        ManagerRegistry $doctrine
    ) {
        $this->filtrationService = $filtrationService;
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentMethodsConfigsRules(PaymentContextInterface $context): array
    {
        if ($context->getBillingAddress()) {
            $methodsConfigsRules = $this->getRepository()->getByDestinationAndCurrencyAndWebsite(
                $context->getBillingAddress(),
                $context->getCurrency(),
                $context->getWebsite()
            );
        } else {
            $methodsConfigsRules = $this->getRepository()->getByCurrencyAndWebsiteWithoutDestination(
                $context->getCurrency(),
                $context->getWebsite()
            );
        }

        return $this->filtrationService->getFilteredPaymentMethodsConfigsRules(
            $methodsConfigsRules,
            $context
        );
    }

    private function getRepository(): PaymentMethodsConfigsRuleRepository
    {
        return $this->doctrine->getRepository(PaymentMethodsConfigsRule::class);
    }
}
