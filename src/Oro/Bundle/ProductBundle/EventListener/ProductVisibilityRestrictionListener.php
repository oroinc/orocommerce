<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Event\BeforeSearchEvent;

class ProductVisibilityRestrictionListener
{
    /**
     * @var ProductManager
     */
    private $productManager;

    /**
     * @var AbstractSearchMappingProvider
     */
    private $mappingProvider;

    /**
     * @param ProductManager                $productManager
     * @param AbstractSearchMappingProvider $mappingProvider
     */
    public function __construct(
        ProductManager $productManager,
        AbstractSearchMappingProvider $mappingProvider
    ) {
        $this->productManager  = $productManager;
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * @param BeforeSearchEvent $event
     */
    public function process(BeforeSearchEvent $event)
    {
        $this->applyQueryRestrictions($event->getQuery());
    }

    /**
     * Run ProductsManager restriction over the search query
     *
     * @param Query $query
     */
    private function applyQueryRestrictions(Query $query)
    {
        if (!$this->isProductInFrom($query)) {
            return;
        }

        if ($this->isStrictlyProductInFrom($query)) {
            $this->productManager->restrictSearchQuery($query);

            return;
        }

        $queryToModify = new Query();
        $queryToModify->from([Product::class]);

        $this->productManager->restrictSearchQuery($queryToModify);

        $restrictions = $queryToModify->getCriteria()->getWhereExpression();

        if ($restrictions === null) {
            return;
        }

        $query->getCriteria()->andWhere(
            Criteria::expr()->orX(
                Criteria::expr()->notExists('sku'),
                $restrictions
            )
        );
    }

    /**
     * @param Query $query
     * @return bool
     */
    private function isStrictlyProductInFrom(Query $query)
    {
        $productEntityAlias =
            $this->mappingProvider->getEntityAlias(Product::class);

        $from = $query->getFrom();

        return $from === [Product::class] || $from === [$productEntityAlias];
    }

    /**
     * @param Query $query
     * @return bool
     */
    private function isProductInFrom(Query $query)
    {
        $productEntityAlias =
            $this->mappingProvider->getEntityAlias(Product::class);

        $allowedEntries = [
            $productEntityAlias,
            Product::class,
            '*'
        ];

        $from = $query->getFrom();

        $result = array_intersect($from, $allowedEntries);

        return !empty($result);
    }
}
