<?php

namespace Oro\Bundle\ShippingBundle\Provider\Price\Configured\Basic;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Provider\Price\Configured\ShippingConfiguredPriceProviderInterface;
use Oro\Bundle\ShippingBundle\Provider\Price\ShippingPriceProviderInterface;

class BasicShippingConfiguredPriceProvider implements ShippingConfiguredPriceProviderInterface
{
    /**
     * @var ShippingPriceProviderInterface
     */
    private $shippingPriceProvider;

    public function __construct(ShippingPriceProviderInterface $shippingPriceProvider)
    {
        $this->shippingPriceProvider = $shippingPriceProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getApplicableMethodsViews(
        ComposedShippingMethodConfigurationInterface $configuration,
        ShippingContextInterface $context
    ) {
        return $this->shippingPriceProvider->getApplicableMethodsViews($context);
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
        return $this->shippingPriceProvider->getPrice($context, $methodId, $methodTypeId);
    }
}
