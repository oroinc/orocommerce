<?php

namespace Oro\Bundle\ProductBundle\RelatedItem\UpsellProduct;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Provider\ProductListBlockConfigInterface;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedItemConfigProviderInterface;

/**
 * The configuration provider for upsell products.
 */
class UpsellProductConfigProvider implements
    RelatedItemConfigProviderInterface,
    ProductListBlockConfigInterface
{
    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function isEnabled(): bool
    {
        return (bool)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::UPSELL_PRODUCTS_ENABLED)
        );
    }

    public function getLimit(): int
    {
        return (int)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::MAX_NUMBER_OF_UPSELL_PRODUCTS),
        );
    }

    /**
     * {@inheritdoc}
     * Up-sell products are not bidirectional, since we are trying to sell more expensive product
     */
    public function isBidirectional(): bool
    {
        return false;
    }

    public function getMinimumItems(): int
    {
        return (int)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::UPSELL_PRODUCTS_MIN_ITEMS)
        );
    }

    public function getMaximumItems(): int
    {
        return (int)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::UPSELL_PRODUCTS_MAX_ITEMS)
        );
    }

    public function isSliderEnabledOnMobile(): bool
    {
        return (bool)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::UPSELL_PRODUCTS_USE_SLIDER_ON_MOBILE)
        );
    }

    public function isAddButtonVisible(): bool
    {
        return (bool)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::UPSELL_PRODUCTS_SHOW_ADD_BUTTON)
        );
    }

    public function getProductListType(): string
    {
        return 'upsell_products';
    }
}
