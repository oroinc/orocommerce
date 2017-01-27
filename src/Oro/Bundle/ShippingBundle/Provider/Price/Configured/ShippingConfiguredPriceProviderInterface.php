<?php

namespace Oro\Bundle\ShippingBundle\Provider\Price\Configured;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;

interface ShippingConfiguredPriceProviderInterface
{
    /**
     * @param ComposedShippingMethodConfigurationInterface $configuration
     * @param ShippingContextInterface $context
     *
     * @return ShippingMethodViewCollection
     */
    public function getApplicableMethodsViews(
        ComposedShippingMethodConfigurationInterface $configuration,
        ShippingContextInterface $context
    );

    /**
     * @param string $methodId
     * @param string $methodTypeId
     * @param ComposedShippingMethodConfigurationInterface $configuration
     * @param ShippingContextInterface $context
     *
     * @return Price|null
     */
    public function getPrice(
        $methodId,
        $methodTypeId,
        ComposedShippingMethodConfigurationInterface $configuration,
        ShippingContextInterface $context
    );
}
