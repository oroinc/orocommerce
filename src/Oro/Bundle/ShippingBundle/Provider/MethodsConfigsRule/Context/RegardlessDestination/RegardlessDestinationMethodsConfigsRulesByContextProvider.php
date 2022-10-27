<?php

namespace Oro\Bundle\ShippingBundle\Provider\MethodsConfigsRule\Context\RegardlessDestination;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Provider\MethodsConfigsRule\Context\MethodsConfigsRulesByContextProviderInterface;
use Oro\Bundle\ShippingBundle\RuleFiltration\MethodsConfigsRulesFiltrationServiceInterface;

class RegardlessDestinationMethodsConfigsRulesByContextProvider implements MethodsConfigsRulesByContextProviderInterface
{
    /**
     * @var MethodsConfigsRulesFiltrationServiceInterface
     */
    private $filtrationService;

    /**
     * @var ShippingMethodsConfigsRuleRepository
     */
    private $repository;

    public function __construct(
        MethodsConfigsRulesFiltrationServiceInterface $filtrationService,
        ShippingMethodsConfigsRuleRepository $repository
    ) {
        $this->filtrationService = $filtrationService;
        $this->repository = $repository;
    }

    /**
     * {@inheritDoc}
     */
    public function getShippingMethodsConfigsRules(ShippingContextInterface $context)
    {
        if ($context->getShippingAddress()) {
            $methodsConfigsRules = $this->repository->getByDestinationAndCurrencyAndWebsite(
                $context->getShippingAddress(),
                $context->getCurrency(),
                $context->getWebsite()
            );
        } else {
            $methodsConfigsRules = $this->repository->getByCurrencyAndWebsite(
                $context->getCurrency(),
                $context->getWebsite()
            );
        }

        return $this->filtrationService->getFilteredShippingMethodsConfigsRules(
            $methodsConfigsRules,
            $context
        );
    }
}
