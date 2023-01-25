<?php

namespace Oro\Bundle\CheckoutBundle\Layout\Extension;

use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

/**
 * Layout context to define if line items grouping or multiple shipping configuration is enabled.
 */
class MultiShippingContextConfigurator implements ContextConfiguratorInterface
{
    private const MULTI_SHIPPING_OPTION_NAME = 'multi_shipping_enabled';
    private const GROUPED_LINE_ITEMS_OPTION_NAME = 'grouped_line_items_enabled';

    private ConfigProvider $configProvider;

    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    public function configureContext(ContextInterface $context): void
    {
        $context->getResolver()
            ->setRequired([self::MULTI_SHIPPING_OPTION_NAME, self::GROUPED_LINE_ITEMS_OPTION_NAME])
            ->setAllowedTypes(self::MULTI_SHIPPING_OPTION_NAME, ['bool'])
            ->setAllowedTypes(self::GROUPED_LINE_ITEMS_OPTION_NAME, ['bool']);

        $context->set(
            self::MULTI_SHIPPING_OPTION_NAME,
            $this->configProvider->isShippingSelectionByLineItemEnabled()
        );

        $context->set(
            self::GROUPED_LINE_ITEMS_OPTION_NAME,
            $this->configProvider->isLineItemsGroupingEnabled()
        );
    }
}
