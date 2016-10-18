<?php

namespace Oro\Bundle\ProductBundle\Search;

use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchRepository;

class ProductRepository extends WebsiteSearchRepository
{
    /**
     * @param array $skus
     * @return Item[]
     */
    public function searchFilteredBySkus(array $skus)
    {
        $searchQuery = $this->createQuery();
        $searchQuery->addSelect('sku');
        $searchQuery->addSelect('title_LOCALIZATION_ID');

        $searchQuery->getQuery()->getCriteria()
            ->andWhere(Criteria::expr()->contains('sku', implode(', ', $skus)));

        return $searchQuery->getResult()->getElements();
    }
}
