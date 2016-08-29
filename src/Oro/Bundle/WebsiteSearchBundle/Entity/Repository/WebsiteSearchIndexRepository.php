<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity\Repository;

use Oro\Bundle\SearchBundle\Entity\Repository\SearchIndexRepository;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;

class WebsiteSearchIndexRepository extends SearchIndexRepository
{
    /**
     * @param array $entityIds
     * @param string $entityClass
     * @param string|null $entityAlias
     * @return Item[]
     */
    public function getEntitiesToRemove(array $entityIds, $entityClass, $entityAlias = null)
    {
        if (empty($entityIds)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('item');
        $queryBuilder
            ->andWhere($queryBuilder->expr()->in('item.recordId', ':entityIds'))
            ->andWhere($queryBuilder->expr()->eq('item.entity', ':entityClass'))
            ->setParameters([
                'entityClass' => $entityClass,
                'entityIds' => $entityIds
            ])
            ->orderBy('item.alias, item.recordId');

        if (null !== $entityAlias) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq('item.alias', ':entityAlias'))
                ->setParameter('entityAlias', $entityAlias);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return int
     */
    public function getCount()
    {
        return $this->createQueryBuilder('item')
           ->select('count(item)')
           ->getQuery()
           ->getSingleScalarResult();
    }
}
