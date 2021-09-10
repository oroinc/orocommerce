<?php

namespace Oro\Bundle\SaleBundle\Quote\Shipping\Configuration;

use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationBuilderFactoryInterface;
use Oro\Bundle\ShippingBundle\Method\Configuration\Composed\ComposedShippingMethodConfigurationInterface;

class QuoteShippingConfigurationFactory
{
    /**
     * @var ComposedShippingMethodConfigurationBuilderFactoryInterface
     */
    private $shippingMethodConfigurationBuilderFactory;

    public function __construct(
        ComposedShippingMethodConfigurationBuilderFactoryInterface $shippingMethodConfigurationBuilderFactory
    ) {
        $this->shippingMethodConfigurationBuilderFactory = $shippingMethodConfigurationBuilderFactory;
    }

    /**
     * @param Quote $quote
     *
     * @return ComposedShippingMethodConfigurationInterface
     */
    public function createQuoteShippingConfig(Quote $quote)
    {
        return $this->shippingMethodConfigurationBuilderFactory->createBuilder()
            ->buildIsAllowUnlistedShippingMethod($quote)
            ->buildIsOverriddenCost($quote)
            ->buildIsShippingMethodLocked($quote)
            ->buildShippingCost($quote)
            ->buildShippingMethod($quote)
            ->buildShippingMethodType($quote)
            ->getResult();
    }
}
