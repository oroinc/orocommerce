<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping;

use Oro\Bundle\CheckoutBundle\DependencyInjection\Configuration;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * Implements logic to get configured values related to multi shipping functionality.
 */
class ConfigProvider
{
    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Grouping line items is not supported without multi shipping functionality.
     *
     * @return bool
     */
    public function isLineItemsGroupingEnabled(): bool
    {
        return $this->isShippingSelectionByLineItemEnabled()
            && (bool)$this->configManager->get($this->buildConfigKey(Configuration::ENABLE_LINE_ITEMS_GROUPING));
    }

    public function getGroupLineItemsByField(): ?string
    {
        return $this->configManager->get($this->buildConfigKey(Configuration::GROUP_LINE_ITEMS_BY));
    }

    public function isCreateSubOrdersForEachGroupEnabled(): bool
    {
        return $this->isLineItemsGroupingEnabled()
            && $this->configManager->get($this->buildConfigKey(Configuration::CREATE_SUBORDERS_FOR_EACH_GROUP));
    }

    public function isShowSubordersInOrderHistoryEnabled(): bool
    {
        return $this->configManager->get($this->buildConfigKey(Configuration::SHOW_SUBORDERS_IN_ORDER_HISTORY));
    }

    public function isShowMainOrdersAndSubOrdersInOrderHistoryEnabled(): bool
    {
        return $this->isShowSubordersInOrderHistoryEnabled() && $this->isShowMainOrderInOrderHistoryEnabled();
    }

    public function isShowMainOrderInOrderHistoryDisabled(): bool
    {
        return $this->isShowSubordersInOrderHistoryEnabled() && !$this->isShowMainOrderInOrderHistoryEnabled();
    }

    public function isShippingSelectionByLineItemEnabled(): bool
    {
        return $this->configManager->get(
            $this->buildConfigKey(Configuration::ENABLE_SHIPPING_METHOD_SELECTION_PER_LINE_ITEM)
        );
    }

    /**
     * Configuration depends on isShowSubordersInOrderHistoryEnabled config and should not be used without additional
     * check of this config.
     *
     * @return bool
     */
    private function isShowMainOrderInOrderHistoryEnabled(): bool
    {
        return $this->configManager->get(
            $this->buildConfigKey(Configuration::SHOW_MAIN_ORDERS_IN_ORDER_HISTORY)
        );
    }

    private function buildConfigKey(string $key): string
    {
        return 'oro_checkout.' . $key;
    }
}
