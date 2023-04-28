<?php

namespace Oro\Bundle\ProductBundle\Model\Builder;

use Oro\Bundle\ProductBundle\Model\Mapping\ProductMapperInterface;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;

/**
 * Adapts ProductMapperInterface to use in QuickAddRowCollectionBuilder without BC breaks.
 */
class QuickAddRowProductMapperAdapter implements QuickAddRowProductMapperInterface
{
    private ProductMapperInterface $productMapper;

    public function __construct(ProductMapperInterface $productMapper)
    {
        $this->productMapper = $productMapper;
    }

    public function mapProducts(QuickAddRowCollection $collection): void
    {
        $this->productMapper->mapProducts($collection);
    }
}
