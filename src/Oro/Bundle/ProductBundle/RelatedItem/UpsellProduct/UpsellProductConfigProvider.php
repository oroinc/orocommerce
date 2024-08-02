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
    public function isEnabled()
    {
        return (bool)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::UPSELL_PRODUCTS_ENABLED)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getLimit()
    {
        return (int)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::MAX_NUMBER_OF_UPSELL_PRODUCTS),
        );
    }

    /**
     * {@inheritdoc}
     * Up-sell products are not bidirectional, since we are trying to sell more expensive product
     */
    public function isBidirectional()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getMinimumItems()
    {
        return (int)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::UPSELL_PRODUCTS_MIN_ITEMS)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximumItems()
    {
        return (int)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::UPSELL_PRODUCTS_MAX_ITEMS)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isSliderEnabledOnMobile()
    {
        return (bool)$this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::UPSELL_PRODUCTS_USE_SLIDER_ON_MOBILE)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function isAddButtonVisible()
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
