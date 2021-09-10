<?php

namespace Oro\Bundle\ShippingBundle\Tools;

/**
 * DatagridAwareRouteHelperInterface should be implemented by classes that can generates URL or URI
 * for the Datagrid filtered by parameters
 */
interface DatagridAwareRouteHelperInterface
{
    /**
     * Generates URL or URI for the Datagrid filtered by parameters
     *
     * @param array  $filters
     * @param int    $referenceType
     *
     * @return string
     */
    public function generate(array $filters, int $referenceType);
}
