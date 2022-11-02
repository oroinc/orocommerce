<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity\Repository;

use Oro\Bundle\BatchBundle\ORM\Query\ResultIterator\IdentifierHydrator;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

/**
 * Get chunk of ids for given entity.
 */
class EntityIdentifierRepository
{
    const QUERY_LIMIT = 100000;

    /**
     * @var DoctrineHelper
     */
    private $doctrineHelper;

    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param string $entityClass
     * @return \Generator
     */
    public function getIds($entityClass)
    {
        $cursor = 0;
        while (true) {
            $results = $this->getChunksOfIds($entityClass, $cursor);
            yield from $results;
            if (count($results) < self::QUERY_LIMIT) {
                break;
            }
            $cursor = end($results);
        }
    }

    /**
     * @param string $entityClass
     * @param integer $cursor
     * @return array
     */
    private function getChunksOfIds($entityClass, $cursor)
    {
        $idColumn = $this->doctrineHelper->getSingleEntityIdentifierFieldName($entityClass);

        $query = $this->doctrineHelper->getEntityRepository($entityClass)
            ->createQueryBuilder('entity')
            ->select("entity.$idColumn")
            ->where("entity.$idColumn > :cursor")
            ->setParameter('cursor', $cursor)
            ->orderBy("entity.$idColumn")
            ->setMaxResults(self::QUERY_LIMIT)
            ->getQuery();

        $identifierHydrationMode = 'IdentifierHydrator';
        $query
            ->getEntityManager()
            ->getConfiguration()
            ->addCustomHydrationMode($identifierHydrationMode, IdentifierHydrator::class);

        return $query->getResult($identifierHydrationMode);
    }
}
