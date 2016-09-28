<?php

namespace Oro\Bundle\ProductBundle\Search\Repository;

use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\SearchBundle\Search\Repository\AbstractSearchQueryRepository;

class ProductRepository extends AbstractSearchQueryRepository
{
    /**
     * @param array $skus
     * @return SearchQueryInterface
     */
    public function getFilterSkuQuery($skus)
    {
        $searchQuery = $this->getQueryBuilder();

        // Convert to uppercase for insensitive search in all DB
        $upperCaseSkus = array_map("strtoupper", $skus);

        $searchQuery->setFrom('product')
            ->addSelect('sku')
            ->getCriteria()
            // todo add uppercase sku
            ->addWhere(Criteria::expr()->in('sku', $upperCaseSkus));

        return $searchQuery;
    }
}
