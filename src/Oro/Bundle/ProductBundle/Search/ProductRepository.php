<?php

namespace Oro\Bundle\ProductBundle\Search;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchRepository;

class ProductRepository extends WebsiteSearchRepository
{
    /**
     * @param $search string
     * @param $firstResult int
     * @param $maxResults int
     * @return \Oro\Bundle\SearchBundle\Query\SearchQueryInterface
     */
    public function getProductSearchQuery($search, $firstResult, $maxResults)
    {
        $searchQuery = $this->createQuery();
        $searchQuery->setFirstResult($firstResult);
        $searchQuery->setMaxResults($maxResults);
        $alias = $this->getMappingProvider()->getEntityAlias(Product::class);
        $searchQuery
            ->setFrom([$alias]);
        $searchQuery->addSelect('sku');
        $searchQuery->addSelect('title_LOCALIZATION_ID');
        $searchQuery->addWhere(Criteria::expr()->contains('sku', $search));

        return $searchQuery;
    }
}
