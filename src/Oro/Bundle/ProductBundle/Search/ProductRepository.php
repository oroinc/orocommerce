<?php

namespace Oro\Bundle\ProductBundle\Search;

use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\WebsiteSearchBundle\Query\WebsiteSearchRepository;

class ProductRepository extends WebsiteSearchRepository
{
    /**
     * @var string[]
     */
    private static $defaultSelectFields = [
        'integer.product_id',
        'text.title',
        'text.sku',
    ];

    /**
     * @param $id
     * @return \Oro\Bundle\SearchBundle\Query\Result\Item|void
     */
    public function findOne($id)
    {
        $searchQuery = $this->createQuery();

        $alias = $this->getMappingProvider()->getEntityAlias($this->getEntityName());

        $searchQuery->getQuery()->from([$alias]);
        $searchQuery->getQuery()->select(self::$defaultSelectFields);
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
}
