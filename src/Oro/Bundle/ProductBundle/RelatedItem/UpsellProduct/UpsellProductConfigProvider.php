<?php

namespace Oro\Bundle\ProductBundle\RelatedItem\UpsellProduct;

use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\RelatedItem\AbstractRelatedItemConfigProvider;

class UpsellProductConfigProvider extends AbstractRelatedItemConfigProvider
{
    /**
     * {@inheritdoc}
     */
    public function isEnabled()
    {
        return $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::UPSELL_PRODUCTS_ENABLED));
    }

    /**
     * {@inheritdoc}
     */
    public function getLimit()
    {
        return $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::MAX_NUMBER_OF_UPSELL_PRODUCTS));
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
        return $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::UPSELL_PRODUCTS_MIN_ITEMS));
    }

    /**
     * {@inheritdoc}
     */
    public function getMaximumItems()
    {
        return $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::UPSELL_PRODUCTS_MAX_ITEMS));
    }

    /**
     * {@inheritdoc}
     */
    public function isSliderEnabledOnMobile()
    {
        return $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::UPSELL_PRODUCTS_USE_SLIDER_ON_MOBILE));
    }

    /**
     * {@inheritdoc}
     */
    public function isAddButtonVisible()
    {
        return $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::UPSELL_PRODUCTS_SHOW_ADD_BUTTON));
    }
}
