<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Manager\ProductManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Event\BeforeSearchEvent;
use Oro\Bundle\SearchBundle\Query\Criteria\ExpressionBuilder;
use Oro\Bundle\SearchBundle\Query\Query;

class ProductVisibilityRestrictionListener
{
    /**
     * @var ProductManager
     */
    private $productManager;

    /**
     * @var ExpressionBuilder
     */
    private $expressionBuilder;

    /**
     * @param ProductManager $productManager
     * @param ExpressionBuilder $expressionBuilder
     */
    public function __construct(
        ProductManager $productManager,
        ExpressionBuilder $expressionBuilder
    ) {
        $this->productManager = $productManager;
        $this->expressionBuilder = $expressionBuilder;
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
            $this->productManager->restrictSearchEngineQuery($query);

            return;
        }

        $queryToModify = new Query();
        $queryToModify->from([Product::class]);

        $this->productManager->restrictSearchEngineQuery($queryToModify);

        $restrictions = $queryToModify->getCriteria()->getWhereExpression();

        if ($restrictions === null) {
            return;
        }

        $query->getCriteria()->andWhere(
            $this->expressionBuilder->orX(
                $this->expressionBuilder->isNull('sku'),
                $restrictions
            )
        );
    }
}
