<?php

namespace Oro\Bundle\ShippingBundle\Provider\Price\Configured\Decorator\OverriddenCost;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\Decorator\AbstractShippingConfiguredPriceProviderDecorator;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\ShippingConfiguredPriceProviderInterface;

class OverriddenCostShippingConfiguredPriceProviderDecorator extends AbstractShippingConfiguredPriceProviderDecorator
{
    /**
     * {@inheritdoc}
     */
    public function __construct(ShippingConfiguredPriceProviderInterface $shippingConfiguredPriceProvider)
    {
        parent::__construct($shippingConfiguredPriceProvider);
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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
