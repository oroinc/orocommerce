<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity\Repository;

use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\SearchBundle\Entity\Repository\SearchIndexRepository;

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

        $parameters = [$entityClass];

        if (null !== $entityAlias) {
            $queryBuilder
                ->andWhere($queryBuilder->expr()->eq('item.alias', ':entityAlias'))
                ->setParameter('entityAlias', $entityAlias);

            $parameters[] = $entityAlias;
        }

        $this->deleteFromIndexTextTable(clone $queryBuilder, $entityIds, $parameters);

        $queryBuilder->delete()->getQuery()->execute();
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $entityIds
     * @param array $stringParameters
     */
    private function deleteFromIndexTextTable(QueryBuilder $queryBuilder, $entityIds, array $stringParameters)
    {
        $subQuery = $queryBuilder->select('DISTINCT item.id')->getQuery()->getSQL();

        $unnamedParameters = str_repeat('?,', count($entityIds) - 1) . '?';
        $subQuery = str_replace('IN (?)', 'IN (' . $unnamedParameters . ')', $subQuery);

        $query = 'DELETE FROM oro_website_search_text WHERE item_id IN (' . $subQuery . ')';

        $parameterTypes = array_fill(0, count($entityIds), \PDO::PARAM_INT);
        $parameterTypes = array_merge($parameterTypes, array_fill(0, count($stringParameters) - 1, \PDO::PARAM_STR));

        $this->_em->getConnection()->executeQuery(
            $query,
            array_merge($entityIds, $stringParameters),
            $parameterTypes
        );
    }
}
