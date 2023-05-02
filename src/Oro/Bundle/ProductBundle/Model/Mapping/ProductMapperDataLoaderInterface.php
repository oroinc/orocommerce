<?php

namespace Oro\Bundle\ProductBundle\Model\Mapping;

/**
 * Loads products for {@see ProductMapper}.
 */
interface ProductMapperDataLoaderInterface
{
    public function loadProducts(array $skusUppercase): array;
}
