<?php

namespace Oro\Bundle\ProductBundle\Model\Builder;

use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;

/**
 * Represents a service to find a product for each row in QuickAddRowCollection.
 */
interface QuickAddRowProductMapperInterface
{
    public function mapProducts(QuickAddRowCollection $collection): void;
}
