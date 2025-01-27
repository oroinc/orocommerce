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
    public function __construct(
        private readonly MethodsConfigsRulesFiltrationServiceInterface $filtrationService,
        private readonly ManagerRegistry $doctrine
    ) {
    }

    #[\Override]
    public function getPaymentMethodsConfigsRules(PaymentContextInterface $context): array
    {
        $currency = $context->getCurrency();
        if (!$currency) {
            return [];
        }

        if ($context->getBillingAddress()) {
            $methodsConfigsRules = $this->getRepository()->getByDestinationAndCurrencyAndWebsite(
                $context->getBillingAddress(),
                $currency,
                $context->getWebsite()
            );
        } else {
            $methodsConfigsRules = $this->getRepository()->getByCurrencyAndWebsiteWithoutDestination(
                $currency,
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
