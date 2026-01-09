<?php

namespace Oro\Bundle\ShippingBundle\Provider\Price\Configured\Decorator;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\ShippingConfiguredPriceProviderInterface;

/**
 * Base decorator for shipping configured price providers.
 *
 * This abstract class provides a foundation for decorators that modify shipping price provider behavior,
 * delegating to the wrapped provider by default and allowing subclasses to override specific methods
 * to apply custom logic.
 */
class AbstractShippingConfiguredPriceProviderDecorator implements ShippingConfiguredPriceProviderInterface
{
    /**
     * @var ShippingConfiguredPriceProviderInterface
     */
    private $shippingConfiguredPriceProvider;

    public function __construct(ShippingConfiguredPriceProviderInterface $shippingConfiguredPriceProvider)
    {
        $this->shippingConfiguredPriceProvider = $shippingConfiguredPriceProvider;
    }

    #[\Override]
    public function getApplicableMethodsViews(
        ComposedShippingMethodConfigurationInterface $configuration,
        ShippingContextInterface $context
    ) {
        return $this->shippingConfiguredPriceProvider->getApplicableMethodsViews($configuration, $context);
    }

    #[\Override]
    public function getPrice(
        $methodId,
        $methodTypeId,
        ComposedShippingMethodConfigurationInterface $configuration,
        ShippingContextInterface $context
    ) {
        return $this->shippingConfiguredPriceProvider->getPrice($methodId, $methodTypeId, $configuration, $context);
    }
}
