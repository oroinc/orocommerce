<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Doctrine\ORM\QueryBuilder;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

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
    /**
     * {@inheritDoc}
     */
    public function process(ContextInterface $context): void
    {
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

        // Filters are not needed as all filtered products already been found.
        $qb->resetDQLPart('where')->getParameters()->clear();
        // Adds one filter that points to concrete products and remove unnecessary parts from the query.
        $qb->andWhere($qb->expr()->in(sprintf('%s.id', $alias), $productIds));
        $qb->setMaxResults(null);
        $qb->setFirstResult(null);
    }
}
