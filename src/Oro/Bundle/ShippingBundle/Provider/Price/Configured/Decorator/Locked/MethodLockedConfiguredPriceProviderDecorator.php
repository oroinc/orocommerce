<?php

namespace Oro\Bundle\ShippingBundle\Provider\Price\Configured\Decorator\Locked;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\Decorator\AbstractShippingConfiguredPriceProviderDecorator;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\ShippingConfiguredPriceProviderInterface;

/**
 * Filters shipping methods to show only the locked method when configured.
 *
 * This decorator restricts available shipping methods to only the pre-configured locked method and type when
 * the shipping method is locked in the configuration, preventing customers from selecting alternative shipping options.
 */
class MethodLockedConfiguredPriceProviderDecorator extends AbstractShippingConfiguredPriceProviderDecorator
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

        if (null === $configuration->getShippingMethod()) {
            return $methodsViews;
        }

        if (false === $configuration->isShippingMethodLocked()) {
            return $methodsViews;
        }

        $resultingMethodViews = clone $methodsViews;

        $methodId = $configuration->getShippingMethod();
        $methodTypeId = $configuration->getShippingMethodType();

        if (false === $resultingMethodViews->hasMethodTypeView($methodId, $methodTypeId)) {
            return $resultingMethodViews;
        }

        $methodView = $resultingMethodViews->getMethodView($methodId);
        $methodTypeView = $resultingMethodViews->getMethodTypeView($methodId, $methodTypeId);

        $resultingMethodViews
            ->clear()
            ->addMethodView($methodId, $methodView)
            ->addMethodTypeView($methodId, $methodTypeId, $methodTypeView);

        return $resultingMethodViews;
    }

    #[\Override]
    public function getPrice(
        $methodId,
        $methodTypeId,
        ComposedShippingMethodConfigurationInterface $configuration,
        ShippingContextInterface $context
    ) {
        return parent::getPrice($methodId, $methodTypeId, $configuration, $context);
    }
}
