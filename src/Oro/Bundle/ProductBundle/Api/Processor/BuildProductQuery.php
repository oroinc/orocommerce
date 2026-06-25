<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ApiBundle\Processor\ListContext;
use Oro\Bundle\ApiBundle\Processor\Shared\Provider\TotalCountCalculator;
use Oro\Bundle\BatchBundle\ORM\QueryBuilder\CountQueryBuilderOptimizer;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;
use Oro\Component\EntitySerializer\QueryResolver;

/**
 * Improves the speed of receiving products due the optimization of query.
 * The problem with the performance is that SQL server works slowly for queries that have a lot of left joins
 * together with LIMIT, OFFSET and ORDER BY statements.
 * To fix this problem we split the query on two queries. The first one loads product IDs using all required joins,
 * filters and LIMIT, OFFSET and ORDER BY statements. The second one loads all required data using
 * the filter by the product IDs and as result LIMIT, OFFSET and ORDER BY statements are not needed for this query.
 */
class BuildProductQuery implements ProcessorInterface
{
    private CountQueryBuilderOptimizer $countQueryBuilderOptimizer;
    private QueryResolver $queryResolver;

    public function setCountQueryBuilderOptimizer(CountQueryBuilderOptimizer $countQueryBuilderOptimizer): void
    {
        $this->countQueryBuilderOptimizer = $countQueryBuilderOptimizer;
    }

    public function setQueryResolver(QueryResolver $queryResolver): void
    {
        $this->queryResolver = $queryResolver;
    }

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var ListContext $context */
        $qb = $context->getQuery();
        if (!$qb instanceof QueryBuilder) {
            return;
        }

        $alias = QueryBuilderUtil::getSingleRootAlias($qb);
        $qb->select(sprintf('%s.id', $alias));
        $productIds = $qb->getQuery()->getSingleColumnResult();
        if (!$productIds) {
            $context->setResult([]);

            return;
        }

        $query = clone($qb);
        // sets the custom calculate total callback because
        // the initial query will be changed and cannot be used to calculate the total.
        $context->setTotalCountCallback(
            function () use ($query, $context) {
                $calculator = new TotalCountCalculator($this->countQueryBuilderOptimizer, $this->queryResolver);

                return $calculator->calculateTotalCount($query, $context->getConfig());
            }
        );

        // Filters are not needed as all filtered products already been found.
        $qb->resetDQLPart('where')->getParameters()->clear();
        // Adds one filter that points to concrete products and remove unnecessary parts from the query.
        $qb->andWhere($qb->expr()->in(sprintf('%s.id', $alias), $productIds));
        $qb->setMaxResults(null);
        $qb->setFirstResult(null);
    }
}
