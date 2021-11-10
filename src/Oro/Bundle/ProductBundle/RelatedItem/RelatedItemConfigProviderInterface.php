<?php

namespace Oro\Bundle\ProductBundle\RelatedItem;

/**
 * Represents a configuration provider for related items, like related products, upsell products, etc.
 */
interface RelatedItemConfigProviderInterface
{
    /**
     * @return bool
     */
    public function isEnabled();

    /**
     * @return int
     */
    public function getLimit();

    /**
     * @return bool
     */
    public function isBidirectional();

    /**
     * @return int
     */
    public function getMinimumItems();

    /**
     * @return int
     */
    public function getMaximumItems();

    /**
     * @return bool
     */
    public function isSliderEnabledOnMobile();

    /**
     * @return bool
     */
    public function isAddButtonVisible();
}
