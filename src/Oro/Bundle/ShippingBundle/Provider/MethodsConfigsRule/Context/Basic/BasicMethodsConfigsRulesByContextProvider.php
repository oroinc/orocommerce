<?php

namespace Oro\Bundle\ShippingBundle\Provider\MethodsConfigsRule\Context\Basic;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ShippingMethodOrganizationProvider;
use Oro\Bundle\ShippingBundle\Provider\MethodsConfigsRule\Context\MethodsConfigsRulesByContextProviderInterface;
use Oro\Bundle\ShippingBundle\RuleFiltration\MethodsConfigsRulesFiltrationServiceInterface;

/**
 * Provides shipping method config rules.
 */
class BasicMethodsConfigsRulesByContextProvider implements MethodsConfigsRulesByContextProviderInterface
{
    public function __construct(
        private readonly MethodsConfigsRulesFiltrationServiceInterface $filtrationService,
        private readonly ManagerRegistry $doctrine,
        private readonly ShippingMethodOrganizationProvider $organizationProvider
    ) {
    }

    #[\Override]
    public function getShippingMethodsConfigsRules(ShippingContextInterface $context): array
    {
        $currency = $context->getCurrency();
        if (!$currency) {
            return [];
        }

        if ($context->getShippingAddress()) {
            $methodsConfigsRules = $this->getRepository()->getByDestinationAndCurrencyAndWebsite(
                $context->getShippingAddress(),
                $currency,
                $context->getWebsite(),
                $this->organizationProvider->getOrganization()
            );
        } else {
            $methodsConfigsRules = $this->getRepository()->getByCurrencyAndWebsiteWithoutDestination(
                $currency,
                $context->getWebsite(),
                $this->organizationProvider->getOrganization()
            );
        }

        return $this->filtrationService->getFilteredShippingMethodsConfigsRules(
            $methodsConfigsRules,
            $context
        );
    }

    private function getRepository(): ShippingMethodsConfigsRuleRepository
    {
        return $this->doctrine->getRepository(ShippingMethodsConfigsRule::class);
    }
}
