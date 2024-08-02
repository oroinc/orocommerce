<?php

namespace Oro\Bundle\ProductBundle\Provider;

/**
 * Represents config fields for product list embedded block on storefront
 */
interface ProductListBlockConfigInterface
{
    public function getMinimumItems();

    public function getMaximumItems();

    public function isSliderEnabledOnMobile();

    public function isAddButtonVisible();

    public function getProductListType(): string;
}
