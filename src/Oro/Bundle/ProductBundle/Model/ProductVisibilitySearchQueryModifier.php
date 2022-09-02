<?php

namespace Oro\Bundle\ProductBundle\Model;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Provides methods to apply product visibility rules to a search query.
 */
class ProductVisibilitySearchQueryModifier
{
    public function modifyByInventoryStatus(Query $query, array $productInventoryStatuses)
    {
        $query->getCriteria()->andWhere(
            Criteria::expr()->in('inv_status', $productInventoryStatuses)
        );
    }

    public function modifyByStatus(Query $query, array $statuses)
    {
        $query->getCriteria()->andWhere(
            Criteria::expr()->in('status', $statuses)
        );
    }
}
