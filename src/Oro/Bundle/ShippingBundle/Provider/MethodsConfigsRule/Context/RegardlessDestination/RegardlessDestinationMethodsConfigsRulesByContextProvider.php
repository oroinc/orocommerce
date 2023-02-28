<?php

namespace Oro\Bundle\ShippingBundle\Provider\MethodsConfigsRule\Context\RegardlessDestination;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;
use Oro\Bundle\ShippingBundle\Method\Provider\Integration\ShippingMethodOrganizationProvider;
use Oro\Bundle\ShippingBundle\Provider\MethodsConfigsRule\Context\MethodsConfigsRulesByContextProviderInterface;
use Oro\Bundle\ShippingBundle\RuleFiltration\MethodsConfigsRulesFiltrationServiceInterface;

/**
 * Provides shipping method config rules regardless of a shipping destination.
 */
class RegardlessDestinationMethodsConfigsRulesByContextProvider implements MethodsConfigsRulesByContextProviderInterface
{
    private MethodsConfigsRulesFiltrationServiceInterface $filtrationService;
    private ManagerRegistry $doctrine;
    private ShippingMethodOrganizationProvider $organizationProvider;

    public function __construct(
        MethodsConfigsRulesFiltrationServiceInterface $filtrationService,
        ManagerRegistry $doctrine,
        ShippingMethodOrganizationProvider $organizationProvider
    ) {
        $this->filtrationService = $filtrationService;
        $this->doctrine = $doctrine;
        $this->organizationProvider = $organizationProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingMethodsConfigsRules(ShippingContextInterface $context): array
    {
        if ($context->getShippingAddress()) {
            $methodsConfigsRules = $this->getRepository()->getByDestinationAndCurrencyAndWebsite(
                $context->getShippingAddress(),
                $context->getCurrency(),
                $context->getWebsite(),
                $this->organizationProvider->getOrganization()
            );
        } else {
            $methodsConfigsRules = $this->getRepository()->getByCurrencyAndWebsite(
                $context->getCurrency(),
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
