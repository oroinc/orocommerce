<?php

namespace Oro\Bundle\ShippingBundle\Provider\Price\Configured\Decorator;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\ShippingConfiguredPriceProviderInterface;

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

    /**
     * {@inheritdoc}
     */
    public function getApplicableMethodsViews(
        ComposedShippingMethodConfigurationInterface $configuration,
        ShippingContextInterface $context
    ) {
        return $this->shippingConfiguredPriceProvider->getApplicableMethodsViews($configuration, $context);
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
        return $this->shippingConfiguredPriceProvider->getPrice($methodId, $methodTypeId, $configuration, $context);
    }
}
