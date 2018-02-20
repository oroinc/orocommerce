<?php

namespace Oro\Bundle\InventoryBundle\ORM\Query\ResultIterator;

use Doctrine\ORM\Query;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\IdentifierHydrator;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\IdentifierIterationStrategy;
use Oro\Bundle\InventoryBundle\ORM\Query\ResultIterator\SelectIdentifierWalker as InventoryWalker;
use Oro\Component\DoctrineUtils\ORM\QueryUtil;

class InventoryLevelIteratorStrategy extends IdentifierIterationStrategy
{
    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
    public function initializeDataQuery(Query $query)
    {
        parent::initializeDataQuery($query);
        QueryUtil::addTreeWalker($query, MissingGroupByWalker::class);
    }
}
