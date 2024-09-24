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

    #[\Override]
    public function isEnabled(): bool
    {
        return $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::RELATED_PRODUCTS_ENABLED)
        );
    }

    #[\Override]
    public function getLimit(): int
    {
        return (int)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::MAX_NUMBER_OF_RELATED_PRODUCTS)
        );
    }

    #[\Override]
    public function isBidirectional(): bool
    {
        return (bool)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::RELATED_PRODUCTS_BIDIRECTIONAL),
        );
    }

    #[\Override]
    public function getMinimumItems(): int
    {
        return (int)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::RELATED_PRODUCTS_MIN_ITEMS)
        );
    }

    #[\Override]
    public function getMaximumItems(): int
    {
        return (int)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::RELATED_PRODUCTS_MAX_ITEMS)
        );
    }

    #[\Override]
    public function isSliderEnabledOnMobile(): bool
    {
        return (bool)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::RELATED_PRODUCTS_USE_SLIDER_ON_MOBILE)
        );
    }

    #[\Override]
    public function isAddButtonVisible(): bool
    {
        return (bool)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::RELATED_PRODUCTS_SHOW_ADD_BUTTON)
        );
    }

    #[\Override]
    public function getProductListType(): string
    {
        return 'related_products';
    }
}
