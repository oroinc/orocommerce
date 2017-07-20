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
     * {@inheritDoc}
     * @throws \LogicException
     */
    public function getMinimumItems()
    {
        throw new \LogicException(
            'UpsellProductConfigProvider::getMinimumItems() is not supported.'
        );
    }

    /**
     * {@inheritDoc}
     * @throws \LogicException
     */
    public function getMaximumItems()
    {
        throw new \LogicException(
            'UpsellProductConfigProvider::getMaximumItems() is not supported.'
        );
    }

    /**
     * {@inheritDoc}
     * @throws \LogicException
     */
    public function isSliderEnabledOnMobile()
    {
        throw new \LogicException(
            'UpsellProductConfigProvider::isSliderEnabledOnMobile() is not supported.'
        );
    }

    /**
     * {@inheritDoc}
     * @throws \LogicException
     */
    public function isAddButtonVisible()
    {
        throw new \LogicException(
            'UpsellProductConfigProvider::isAddButtonVisible() is not supported.'
        );
    }
}
