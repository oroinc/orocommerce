<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Provider\ProductTypeProvider;

/**
 * Provides information Product Types available for application.
 */
class ProductTypesProvider
{
    private ProductTypeProvider $productTypeProvider;

    public function __construct(ProductTypeProvider $productTypeProvider)
    {
        $this->productTypeProvider = $productTypeProvider;
    }

    public function isProductTypeEnabled(string $type): bool
    {
        return in_array(strtolower($type), $this->productTypeProvider->getAvailableProductTypes());
    }
}
