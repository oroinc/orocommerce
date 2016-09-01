<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity\Repository;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\EntityBundle\ORM\DatabasePlatformInterface;
use Oro\Bundle\SearchBundle\Entity\Repository\SearchIndexRepository;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;

class WebsiteSearchIndexRepository extends SearchIndexRepository
{
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
            ->setParameters([
                'entityClass' => $entityClass,
                'entityIds' => $entityIds
            ]);

        if (null !== $entityAlias) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq('item.alias', ':entityAlias'))
                ->setParameter('entityAlias', $entityAlias);
        }

        $this->deleteFromIndexTextTable(clone $queryBuilder);

        $queryBuilder->delete()->getQuery()->execute();
    }

    /**
     * We need to remove info manually as fulltext index in MySQL only available in MyISAM engine which doesn't support
     * cascade deletes by a foreign key.
     *
     * @param QueryBuilder $subQueryBuilder
     */
    private function deleteFromIndexTextTable(QueryBuilder $subQueryBuilder)
    {
        $platformName = $this->getEntityManager()->getConnection()->getDatabasePlatform()->getName();

        if (DatabasePlatformInterface::DATABASE_MYSQL === $platformName) {

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
    }
}
