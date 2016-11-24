<?php

namespace Oro\Bundle\ProductBundle\Model;

use Oro\Bundle\ApiBundle\Collection\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;

class ProductVisibilitySearchQueryModifier
{
    /**
     * @param Query $query
     * @param array $productInventoryStatuses
     */
    public function modifyByInventoryStatus(Query $query, array $productInventoryStatuses)
    {
        $query->getCriteria()->andWhere(
            Criteria::expr()->in('inventory_status', $productInventoryStatuses)
        );
    }

    /**
     * @param Query $query
     * @param array $statuses
     */
    public function modifyByStatus(Query $query, array $statuses)
    {
        $query->getCriteria()->andWhere(
            Criteria::expr()->in('status', $statuses)
        );
    }
}
