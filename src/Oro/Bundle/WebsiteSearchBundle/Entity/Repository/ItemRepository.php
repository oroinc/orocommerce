<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;

class ItemRepository extends EntityRepository
{
    /**
     * @param string $currentAlias
     */
    public function removeIndexByAlias($currentAlias)
    {
        $qb = $this->createQueryBuilder('item');
        $qb
            ->where($qb->expr()->eq('item.alias', ':current_alias'))
            ->setParameter('current_alias', $currentAlias);

        $this->deleteFromIndexTextTable(clone $qb);

        $qb->delete()->getQuery()->execute();
    }

    /**
     * @param string $temporaryAlias
     * @param string $currentAlias
     */
    public function renameIndexAlias($temporaryAlias, $currentAlias)
    {
        $qb = $this->createQueryBuilder('item');
        $qb->update()->set('item.alias', ':current_alias')
            ->where($qb->expr()->eq('item.alias', ':temporary_alias'))
            ->setParameter('current_alias', $currentAlias)
            ->setParameter('temporary_alias', $temporaryAlias)
            ->getQuery()
            ->execute();
    }

    /**
     * @param array $entityIds
     * @param string $entityClass
     * @param string|null $entityAlias
     */
    public function removeEntities(array $entityIds, $entityClass, $entityAlias = null)
    {
        if (empty($entityIds)) {
            return;
        }

        $queryBuilder = $this->createQueryBuilder('item');
        $queryBuilder
            ->andWhere($queryBuilder->expr()->in('item.recordId', ':entityIds'))
            ->andWhere($queryBuilder->expr()->eq('item.entity', ':entityClass'))
            ->setParameter('entityClass', $entityClass)
            ->setParameter('entityIds', $entityIds);

        if ($entityAlias) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq('item.alias', ':entityAlias'))
                ->setParameter('entityAlias', $entityAlias);
        }

        $this->deleteFromIndexTextTable(clone $queryBuilder);

        $queryBuilder->delete()->getQuery()->execute();
    }

    /**
     * We need to remove data manually as fulltext index in MySQL is only available in MyISAM engine which doesn't
     * support cascade deletes by a foreign key.
     * @param QueryBuilder $subQueryBuilder
     */
    private function deleteFromIndexTextTable(QueryBuilder $subQueryBuilder)
    {
        $subQueryDQL = $subQueryBuilder->select('item.id')->getDQL();

        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder
            ->from(IndexText::class, 'indexText')
            ->delete()
            ->where($queryBuilder->expr()->in('indexText.item', $subQueryDQL))
            ->setParameters($subQueryBuilder->getParameters())
            ->getQuery()
            ->execute();
    }

    /**
     * Removes index data for given $entityClass or all classes.
     * @param string $entityClass
     */
    public function removeIndexByClass($entityClass = null)
    {
        $queryBuilder = $this->createQueryBuilder('item');

        if ($entityClass) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq('item.entity', ':entityClass'))
                ->setParameter('entityClass', $entityClass);
        }

        $this->deleteFromIndexTextTable(clone $queryBuilder);

        $queryBuilder->delete()->getQuery()->execute();
    }
}
