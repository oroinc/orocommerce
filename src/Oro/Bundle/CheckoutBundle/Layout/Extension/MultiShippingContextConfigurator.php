<?php

namespace Oro\Bundle\CheckoutBundle\Layout\Extension;

use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Component\Layout\ContextConfiguratorInterface;
use Oro\Component\Layout\ContextInterface;

/**
 * Adds information about Multi Shipping to the layout context.
 */
class MultiShippingContextConfigurator implements ContextConfiguratorInterface
{
    /** the "multi_shipping_enabled" options kept to avoid BC break with old layouts */
    private const MULTI_SHIPPING_OPTION_NAME = 'multi_shipping_enabled';
    private const GROUPED_LINE_ITEMS_OPTION_NAME = 'grouped_line_items_enabled';
    private const MULTI_SHIPPING_TYPE_OPTION_NAME = 'multi_shipping_type';

    private ConfigProvider $configProvider;

    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    #[\Override]
    public function configureContext(ContextInterface $context): void
    {
        $context->getResolver()
            ->setRequired([
                self::MULTI_SHIPPING_OPTION_NAME,
                self::GROUPED_LINE_ITEMS_OPTION_NAME,
                self::MULTI_SHIPPING_TYPE_OPTION_NAME
            ])
            ->setAllowedTypes(self::MULTI_SHIPPING_OPTION_NAME, ['bool'])
            ->setAllowedTypes(self::GROUPED_LINE_ITEMS_OPTION_NAME, ['bool'])
            ->setAllowedTypes(self::MULTI_SHIPPING_TYPE_OPTION_NAME, ['string', 'null']);

        $context->set(self::MULTI_SHIPPING_OPTION_NAME, $this->configProvider->isShippingSelectionByLineItemEnabled());
        $context->set(self::GROUPED_LINE_ITEMS_OPTION_NAME, $this->configProvider->isLineItemsGroupingEnabled());
        $multiShippingType = null;
        if (
            $context->has('workflowName')
            && $context->get('workflowName') === 'b2b_flow_checkout'
            && $this->configProvider->isMultiShippingEnabled()
        ) {
            $multiShippingType = $this->configProvider->isShippingSelectionByLineItemEnabled()
                ? 'per_line_item'
                : 'per_line_item_group';
        }
        $context->set(self::MULTI_SHIPPING_TYPE_OPTION_NAME, $multiShippingType);
    }
}
