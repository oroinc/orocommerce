<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
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
     * @param ProductManager $productManager
     */
    public function __construct(
        ProductManager $productManager
    ) {
        $this->productManager = $productManager;
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
        if ($query->getFrom() == [Product::class]) {
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
                Criteria::expr()->isNull('sku'),
                $restrictions
            )
        );
    }
}
