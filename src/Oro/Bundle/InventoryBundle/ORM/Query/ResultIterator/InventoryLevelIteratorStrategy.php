<?php

namespace Oro\Bundle\InventoryBundle\ORM\Query\ResultIterator;

use Doctrine\ORM\Query;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\IdentifierHydrator;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\IdentifierIterationStrategy;
use Oro\Bundle\InventoryBundle\ORM\Query\ResultIterator\SelectIdentifierWalker as InventoryWalker;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;

/**
 * Provides optimized iteration strategy for inventory level queries.
 *
 * This strategy extends the base identifier iteration strategy to customize query execution
 * for inventory level entities. It configures custom hydration modes and tree walkers to
 * efficiently handle inventory level data retrieval and iteration, including special handling
 * for missing `GROUP BY` clauses in complex inventory queries.
 */
class InventoryLevelIteratorStrategy extends IdentifierIterationStrategy
{
    #[\Override]
    public function initializeIdentityQuery(Query $query)
    {
        $identifierHydrationMode = 'IdentifierHydrator';
        $query
            ->getEntityManager()
            ->getConfiguration()
            ->addCustomHydrationMode($identifierHydrationMode, IdentifierHydrator::class);

        $query->setHydrationMode($identifierHydrationMode);

        QueryUtil::addTreeWalker($query, InventoryWalker::class);
    }

    #[\Override]
    public function initializeDataQuery(Query $query)
    {
        parent::initializeDataQuery($query);
        QueryUtil::addTreeWalker($query, MissingGroupByWalker::class);
    }
}
