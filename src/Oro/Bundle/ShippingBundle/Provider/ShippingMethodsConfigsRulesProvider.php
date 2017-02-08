<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Converter\ShippingContextToRuleValuesConverter;
use Oro\Bundle\ShippingBundle\Entity\Repository\ShippingMethodsConfigsRuleRepository;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRule;

class ShippingMethodsConfigsRulesProvider implements ShippingMethodsConfigsRulesProviderInterface
{
    /** @var RuleFiltrationServiceInterface */
    private $filtrationService;

    /** @var ShippingContextToRuleValuesConverter */
    private $converter;

    /** @var ShippingMethodsConfigsRuleRepository */
    private $repository;

    /**
     * @param RuleFiltrationServiceInterface $filtrationService
     * @param ShippingContextToRuleValuesConverter $converter
     * @param ShippingMethodsConfigsRuleRepository $repository
     */
    public function __construct(
        RuleFiltrationServiceInterface $filtrationService,
        ShippingContextToRuleValuesConverter $converter,
        ShippingMethodsConfigsRuleRepository $repository
    ) {
        $this->filtrationService = $filtrationService;
        $this->converter = $converter;
        $this->repository = $repository;
    }

    /**
     * @param array|ShippingMethodsConfigsRule[]
     * @param ShippingContextInterface $context
     * @return array|ShippingMethodsConfigsRule[]
     */
    private function filterByContext(array $methodsConfigsRules, ShippingContextInterface $context)
    {
        $arrayContext = $this->converter->convert($context);

        return $this->filtrationService->getFilteredRuleOwners(
            $methodsConfigsRules,
            $arrayContext
        );
    }

    /**
     * @param ShippingContextInterface $context
     * @return array|ShippingMethodsConfigsRule[]
     */
    public function getFilteredShippingMethodsConfigsRegardlessDestination(ShippingContextInterface $context)
    {
        if ($context->getShippingAddress()) {
            $methodsConfigsRules = $this->repository->getByDestinationAndCurrency(
                $context->getShippingAddress(),
                $context->getCurrency()
            );
        } else {
            $methodsConfigsRules = $this->repository->getByCurrency($context->getCurrency());
        }

        return $this->filterByContext($methodsConfigsRules, $context);
    }

    /**
     * @param ShippingContextInterface $context
     * @return array|ShippingMethodsConfigsRule[]
     */
    public function getAllFilteredShippingMethodsConfigs(ShippingContextInterface $context)
    {
        if ($context->getShippingAddress()) {
            $methodsConfigsRules = $this->repository->getByDestinationAndCurrency(
                $context->getShippingAddress(),
                $context->getCurrency()
            );
        } else {
            $methodsConfigsRules = $this->repository->getByCurrencyWithoutDestination(
                $context->getCurrency()
            );
        }

        return $this->filterByContext($methodsConfigsRules, $context);
    }
}
