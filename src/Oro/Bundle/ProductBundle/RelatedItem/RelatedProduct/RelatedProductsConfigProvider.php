<?php

namespace Oro\Bundle\ProductBundle\RelatedItem\RelatedProduct;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Provider\ProductListBlockConfigInterface;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedItemConfigProviderInterface;

/**
 * The configuration provider for related products.
 */
class RelatedProductsConfigProvider implements
    RelatedItemConfigProviderInterface,
    ProductListBlockConfigInterface
{
    public function __construct(private ConfigManager $configManager)
    {
    }

    public function isEnabled(): bool
    {
        return $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::RELATED_PRODUCTS_ENABLED)
        );
    }

    public function getLimit(): int
    {
        return (int)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::MAX_NUMBER_OF_RELATED_PRODUCTS)
        );
    }

    public function isBidirectional(): bool
    {
        return (bool)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::RELATED_PRODUCTS_BIDIRECTIONAL),
        );
    }

    public function getMinimumItems(): int
    {
        return (int)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::RELATED_PRODUCTS_MIN_ITEMS)
        );
    }

    public function getMaximumItems(): int
    {
        return (int)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::RELATED_PRODUCTS_MAX_ITEMS)
        );
    }

    public function isSliderEnabledOnMobile(): bool
    {
        return (bool)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::RELATED_PRODUCTS_USE_SLIDER_ON_MOBILE)
        );
    }

    public function isAddButtonVisible(): bool
    {
        return (bool)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::RELATED_PRODUCTS_SHOW_ADD_BUTTON)
        );
    }

    public function getProductListType(): string
    {
        return 'related_products';
    }
}
