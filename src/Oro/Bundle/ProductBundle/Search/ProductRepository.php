<?php

namespace Oro\Bundle\ProductBundle\Search;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchRepository;

class ProductRepository extends WebsiteSearchRepository
{
    public function findOne($id)
    {
        $searchQuery = $this->createQuery();

        $alias = $this->getMappingProvider()->getEntityAlias($this->getEntityName());

        $searchQuery->getQuery()->from([$alias]);
        $searchQuery->getQuery()->getCriteria()->andWhere(
            Criteria::expr()->eq('integer.product_id', $id)
        );

        $items = $searchQuery->getResult();

        if ($items->getRecordsCount() < 1) {
            return;
        }

        $item = $items->getElements()[0];

        return $item;
    }
}
