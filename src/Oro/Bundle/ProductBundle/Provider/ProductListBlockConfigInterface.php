<?php

namespace Oro\Bundle\ProductBundle\Provider;

/**
 * Represents config fields for product list embedded block on storefront
 */
interface ProductListBlockConfigInterface
{
    public function getMinimumItems(): int;

    public function getMaximumItems(): int;

    public function isSliderEnabledOnMobile(): bool;

    public function isAddButtonVisible(): bool;

    public function getProductListType(): string;
}
