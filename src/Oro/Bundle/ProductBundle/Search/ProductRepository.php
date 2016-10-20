<?php

namespace Oro\Bundle\ProductBundle\Search;

use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchRepository;

class ProductRepository extends WebsiteSearchRepository
{
    /**
     * @param array $skus
     * @return SearchQueryInterface
     */
    public function getFilterSkuQuery($skus)
    {
        $searchQuery = $this->createQuery();

        // Convert to uppercase for insensitive search in all DB
        $upperCaseSkus = array_map("strtoupper", $skus);

        $searchQuery->setFrom('oro_product_WEBSITE_ID')
            ->addSelect('sku')
            ->getCriteria()
            ->andWhere(Criteria::expr()->contains('sku_uppercase', implode(', ', $upperCaseSkus)));

        return $searchQuery;
    }

    /**
     * @param array $skus
     * @return Item[]
     */
    public function searchFilteredBySkus(array $skus)
    {
        $searchQuery = $this->getFilterSkuQuery($skus);
        $searchQuery->addSelect('title_LOCALIZATION_ID');

        return $searchQuery->getResult()->getElements();
    }
    /**
     * @param $search string
     * @param $firstResult int
     * @param $maxResults int
     * @return SearchQueryInterface
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
