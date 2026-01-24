<?php

namespace Oro\Bundle\ShippingBundle\Provider\Price\Configured;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodViewCollection;

/**
 * Defines the contract for providers that retrieve shipping prices with configuration support.
 *
 * Implementations of this interface provide shipping method views and prices based on both the shipping context and
 * composed configuration, allowing for configuration-based price modifications such as overrides and method locking.
 */
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
