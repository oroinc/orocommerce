<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * Provides configured values related to multi shipping functionality.
 */
class ConfigProvider
{
    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    public function isLineItemsGroupingEnabled(): bool
    {
        return
            $this->isShippingSelectionByLineItemEnabled()
            && $this->configManager->get('oro_checkout.enable_line_item_grouping');
    }

    public function getGroupLineItemsByField(): ?string
    {
        return $this->configManager->get('oro_checkout.group_line_items_by');
    }

    public function isCreateSubOrdersForEachGroupEnabled(): bool
    {
        return
            $this->isLineItemsGroupingEnabled()
            && $this->configManager->get('oro_checkout.create_suborders_for_each_group');
    }

    public function isShowSubordersInOrderHistoryEnabled(): bool
    {
        return $this->configManager->get('oro_checkout.show_suborders_in_order_history');
    }

    public function isShowMainOrdersAndSubOrdersInOrderHistoryEnabled(): bool
    {
        return
            $this->isShowSubordersInOrderHistoryEnabled()
            && $this->isShowMainOrderInOrderHistoryEnabled();
    }

    public function isShowMainOrderInOrderHistoryDisabled(): bool
    {
        return
            $this->isShowSubordersInOrderHistoryEnabled()
            && !$this->isShowMainOrderInOrderHistoryEnabled();
    }

    public function isShippingSelectionByLineItemEnabled(): bool
    {
        return $this->configManager->get('oro_checkout.enable_shipping_method_selection_per_line_item');
    }

    /**
     * This setting depends on the {@see isShowSubordersInOrderHistoryEnabled} setting
     * and should not be used without additional check of this config.
     */
    private function isShowMainOrderInOrderHistoryEnabled(): bool
    {
        return $this->configManager->get('oro_checkout.show_main_orders_in_order_history');
    }
}
