<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity\Repository;

use Oro\Bundle\SearchBundle\Entity\Repository\SearchIndexRepository;

class WebsiteSearchIndexRepository extends SearchIndexRepository
{
    /**
     * Removes search item entities for entities with given $entityClass and $entityIds.
     * If $entityAlias is null then all search item entities are deleted,
     * otherwise only entities with this $entityAlias are deleted.
     *
     * @param array $entityIds
     * @param string $entityClass
     * @param string|null $entityAlias
     */
    public function removeItemEntities(array $entityIds, $entityClass, $entityAlias = null)
    {
        if (empty($entityIds)) {
            return;
        }

        $queryBuilder = $this->createQueryBuilder('item');
        $queryBuilder
            ->andWhere($queryBuilder->expr()->in('item.recordId', ':entityIds'))
            ->andWhere($queryBuilder->expr()->eq('item.entity', ':entityClass'))
            ->setParameters([
                'entityClass' => $entityClass,
                'entityIds' => $entityIds
            ]);

        if (null !== $entityAlias) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq('item.alias', ':entityAlias'))
                ->setParameter('entityAlias', $entityAlias);
        }

        $items = $queryBuilder->getQuery()->getResult();

        $entityManager = $this->getEntityManager();
        foreach ($items as $item) {
            $entityManager->remove($item);
        }

        $entityManager->flush($items);
    }

    /**
     * @return integer
     */
    public function getCount()
    {
       return $this->createQueryBuilder('item')
           ->select('count(item)')
           ->getQuery()
           ->getSingleScalarResult();
    }
}
