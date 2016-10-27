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
            ->addSelect('title_LOCALIZATION_ID')
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

        return $searchQuery->getResult()->getElements();
    }

    /**
     * @param string $search
     * @param int $firstResult
     * @param int $maxResults
     * @return SearchQueryInterface
     */
    public function getSearchQuery($search, $firstResult, $maxResults)
    {
        $searchQuery = $this->createQuery();

        $searchQuery->setFrom('oro_product_WEBSITE_ID')
            ->addSelect('sku')
            ->addSelect('title_LOCALIZATION_ID')
            ->getCriteria()
            ->andWhere(
                Criteria::expr()->contains('all_text_LOCALIZATION_ID', $search)
            )->orderBy(['id' => Criteria::ASC])
            ->setFirstResult($firstResult)
            ->setMaxResults($maxResults);

        return $searchQuery;
    }
}
