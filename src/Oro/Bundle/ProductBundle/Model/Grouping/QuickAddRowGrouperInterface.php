<?php

namespace Oro\Bundle\ProductBundle\Model\Grouping;

use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;

/**
 * Represents a service to group duplicated rows in QuickAddRowCollection.
 */
interface QuickAddRowGrouperInterface
{
    /**
     * Groups duplicated rows in the given quick add row collection.
     */
    public function groupProducts(QuickAddRowCollection $collection): void;
}
