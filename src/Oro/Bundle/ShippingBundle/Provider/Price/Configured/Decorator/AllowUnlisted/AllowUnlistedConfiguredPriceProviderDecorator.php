<?php

namespace Oro\Bundle\ShippingBundle\Provider\Price\Configured\Decorator\AllowUnlisted;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewFactory;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\Decorator\AbstractShippingConfiguredPriceProviderDecorator;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\ShippingConfiguredPriceProviderInterface;

/**
 * Decorator to help quote with allowed unlisted shipping method to return a correct one.
 */
class AllowUnlistedConfiguredPriceProviderDecorator extends AbstractShippingConfiguredPriceProviderDecorator
{
    /**
     * @var ShippingMethodViewFactory
     */
    private $shippingMethodViewFactory;

    public function __construct(
        ShippingMethodViewFactory $shippingMethodViewFactory,
        ShippingConfiguredPriceProviderInterface $shippingConfiguredPriceProvider
    ) {
        $this->shippingMethodViewFactory = $shippingMethodViewFactory;

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

        if (false === $configuration->isAllowUnlistedShippingMethod()) {
            return $methodsViews;
        }

        $resultingMethodViews = clone $methodsViews;

        $methodId = $configuration->getShippingMethod();
        $methodTypeId = $configuration->getShippingMethodType();

        if ($methodsViews->hasMethodTypeView($methodId, $methodTypeId)) {
            return $resultingMethodViews;
        }

        $methodView = $this->shippingMethodViewFactory->createMethodViewByShippingMethod($methodId);
        $methodTypeView = $this->shippingMethodViewFactory
            ->createMethodTypeViewByShippingMethodAndPrice($methodId, $methodTypeId, $configuration->getShippingCost());

        $resultingMethodViews
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
        if ($methodId && $methodTypeId) {
            if (false === $configuration->isAllowUnlistedShippingMethod()) {
                return parent::getPrice($methodId, $methodTypeId, $configuration, $context);
            }

            $shippingMethodViews = parent::getApplicableMethodsViews($configuration, $context);

            if ($shippingMethodViews->hasMethodTypeView($methodId, $methodTypeId)) {
                return parent::getPrice($methodId, $methodTypeId, $configuration, $context);
            }

            return $configuration->getShippingCost();
        }

        return $configuration->getShippingCost();
    }
}
