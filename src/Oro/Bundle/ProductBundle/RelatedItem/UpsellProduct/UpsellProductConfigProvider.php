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

    #[\Override]
    public function isEnabled(): bool
    {
        return (bool)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::UPSELL_PRODUCTS_ENABLED)
        );
    }

    #[\Override]
    public function getLimit(): int
    {
        return (int)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::MAX_NUMBER_OF_UPSELL_PRODUCTS),
        );
    }

    #[\Override]
    public function isBidirectional(): bool
    {
        return false;
    }

    #[\Override]
    public function getMinimumItems(): int
    {
        return (int)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::UPSELL_PRODUCTS_MIN_ITEMS)
        );
    }

    #[\Override]
    public function getMaximumItems(): int
    {
        return (int)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::UPSELL_PRODUCTS_MAX_ITEMS)
        );
    }

    #[\Override]
    public function isSliderEnabledOnMobile(): bool
    {
        return (bool)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::UPSELL_PRODUCTS_USE_SLIDER_ON_MOBILE)
        );
    }

    #[\Override]
    public function isAddButtonVisible(): bool
    {
        return (bool)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::UPSELL_PRODUCTS_SHOW_ADD_BUTTON)
        );
    }

    #[\Override]
    public function getProductListType(): string
    {
        return 'upsell_products';
    }
}
