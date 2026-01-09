<?php

namespace Oro\Bundle\ShippingBundle\Provider\Price\Configured\Decorator\OverriddenCost;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\Decorator\AbstractShippingConfiguredPriceProviderDecorator;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\ShippingConfiguredPriceProviderInterface;

/**
 * Applies overridden shipping costs to all shipping method types.
 *
 * This decorator replaces calculated shipping prices with a configured override cost when the shipping cost override
 * is enabled in the configuration, allowing custom pricing to take precedence over standard rate calculations.
 */
class OverriddenCostShippingConfiguredPriceProviderDecorator extends AbstractShippingConfiguredPriceProviderDecorator
{
    public function __construct(ShippingConfiguredPriceProviderInterface $shippingConfiguredPriceProvider)
    {
        parent::__construct($shippingConfiguredPriceProvider);
    }

    #[\Override]
    public function getApplicableMethodsViews(
        ComposedShippingMethodConfigurationInterface $configuration,
        ShippingContextInterface $context
    ) {
        $methodsViews = parent::getApplicableMethodsViews($configuration, $context);

        if (false === $configuration->isOverriddenShippingCost()) {
            return $methodsViews;
        }

        $overriddenShippingCost = $configuration->getShippingCost();

        $resultingMethodsViews = clone $methodsViews;

        foreach ($methodsViews->getAllMethodsTypesViews() as $methodId => $methodTypes) {
            foreach ($methodTypes as $methodTypeId => $methodTypeView) {
                $methodTypeViewWithChangedPrice = $methodTypeView;
                $methodTypeViewWithChangedPrice['price'] = $overriddenShippingCost;

                $resultingMethodsViews->removeMethodTypeView($methodId, $methodTypeId);
                $resultingMethodsViews->addMethodTypeView($methodId, $methodTypeId, $methodTypeViewWithChangedPrice);
            }
        }

        return $resultingMethodsViews;
    }

    #[\Override]
    public function getPrice(
        $methodId,
        $methodTypeId,
        ComposedShippingMethodConfigurationInterface $configuration,
        ShippingContextInterface $context
    ) {
        if (false === $configuration->isOverriddenShippingCost()) {
            return parent::getPrice($methodId, $methodTypeId, $configuration, $context);
        }

        return $configuration->getShippingCost();
    }
}
