<?php

namespace Oro\Bundle\ShippingBundle\RuleFiltration\Basic;

use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Converter\ShippingContextToRulesValuesConverterInterface;
use Oro\Bundle\ShippingBundle\RuleFiltration\MethodsConfigsRulesFiltrationServiceInterface;

/**
 * Filters shipping method configuration rules based on shipping context.
 *
 * This service evaluates shipping rules against the current shipping context, filtering out rules that don't match
 * the context conditions (destination, line items, customer, etc.),
 * and returning only the applicable shipping method configurations.
 */
class BasicMethodsConfigsRulesFiltrationService implements MethodsConfigsRulesFiltrationServiceInterface
{
    /**
     * @var RuleFiltrationServiceInterface
     */
    private $filtrationService;

    /**
     * @var ShippingContextToRulesValuesConverterInterface
     */
    private $shippingContextToRulesValuesConverter;

    public function __construct(
        RuleFiltrationServiceInterface $filtrationService,
        ShippingContextToRulesValuesConverterInterface $converter
    ) {
        $this->filtrationService = $filtrationService;
        $this->shippingContextToRulesValuesConverter = $converter;
    }

    #[\Override]
    public function getFilteredShippingMethodsConfigsRules(
        array $shippingMethodsConfigsRules,
        ShippingContextInterface $context
    ) {
        $arrayContext = $this->shippingContextToRulesValuesConverter->convert($context);

        return $this->filtrationService->getFilteredRuleOwners(
            $shippingMethodsConfigsRules,
            $arrayContext
        );
    }
}
