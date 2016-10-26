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
//        $query->setFrom(Product::class);
        //$query->getQuery()->getCriteria()->andWhere('id = ')
//        $result = $query->getResult();


//        $query->getCriteria()->orderBy(['stringValue' => Query::ORDER_ASC]);

        $entityName = $this->getEntityName();
        $alias = $this->getMappingProvider()->getEntityAlias($this->getEntityName());

        $searchQuery->getQuery()->from([$alias]);
        //$searchQuery->getQuery()->select(['id']);
        $searchQuery->getQuery()->getCriteria()->andWhere(
            Criteria::expr()->eq('id', $id)
        );

        $items = $searchQuery->getResult();
        $items;

        return $result;
        //$product = $query->getFirstResult();
    }
}
