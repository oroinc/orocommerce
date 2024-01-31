<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity\Repository;

use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIteratorInterface;
use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\IdentifierHydrator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Get chunk of ids for given entity.
 */
class EntityIdentifierRepository
{
    private const QUERY_LIMIT = 100000;

    public function __construct(private DoctrineHelper $doctrineHelper)
    {
    }

    public function getIds($entityClass): BufferedQueryResultIteratorInterface
    {
        $idColumn = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityClass);
        $em = $this->doctrineHelper->getEntityManager($entityClass);

        $query = $em->getRepository($entityClass)
            ->createQueryBuilder('entity')
            ->select("entity.$idColumn")
            ->getQuery()
            ->useQueryCache(false)
            ->disableResultCache();

        $identifierHydrationMode = 'IdentifierHydrator';
        $em->getConfiguration()
            ->addCustomHydrationMode($identifierHydrationMode, IdentifierHydrator::class);

        return (new BufferedIdentityQueryResultIterator($query))
            ->setHydrationMode($identifierHydrationMode)
            ->setBufferSize(self::QUERY_LIMIT)
            ->setPageCallback(fn () => $em->clear());
    }
}
