<?php

namespace Oro\Bundle\ShippingBundle\RuleFiltration\Basic;

use Oro\Bundle\RuleBundle\RuleFiltration\RuleFiltrationServiceInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Converter\ShippingContextToRulesValuesConverterInterface;
use Oro\Bundle\ShippingBundle\RuleFiltration\MethodsConfigsRulesFiltrationServiceInterface;

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

    /**
     * {@inheritDoc}
     */
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
