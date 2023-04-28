<?php

namespace Oro\Bundle\ProductBundle\Model\Builder;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\Mapping\ProductMapperDataLoaderInterface;

/**
 * Loads products for the service that maps a product for each row in QuickAddRowCollection.
 */
class QuickAddRowDataLoader implements ProductMapperDataLoaderInterface
{
    private QuickAddRowProductLoader $loader;

    public function __construct(QuickAddRowProductLoader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * {@inheritDoc}
     *
     * @param string[] $skusUppercase
     *
     * @return Product[]
     */
    public function loadProducts(array $skusUppercase): array
    {
        return $this->loader->loadProducts($skusUppercase);
    }
}
