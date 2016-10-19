<?php

namespace Oro\Bundle\CustomerBundle\Entity\VisibilityResolved\Repository;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\QueryBuilder;

/**
 * @method ClassMetadataInfo getClassMetadata()
 * @method EntityManager getEntityManager()
 * @method string getEntityName()
 * @method QueryBuilder createQueryBuilder($alias)
 */
trait BasicOperationRepositoryTrait
{
    /**
     * @param array $data
     */
    public function insertEntity(array $data)
    {
        $metadata = $this->getClassMetadata();
        $columns = [];
        $parameters = [];

        foreach ($data as $fieldName => $fieldValue) {
            if ($metadata->hasField($fieldName)) {
                $column = $metadata->getColumnName($fieldName);
            } elseif ($metadata->hasAssociation($fieldName)) {
                $column = $metadata->getAssociationMapping($fieldName)['joinColumns'][0]['name'];
            } else {
                $column = $fieldName;
            }
            $columns[$column] = '?';

            if (is_object($fieldValue)) {
                $parameters[] = $this->getEntityIdentifier($fieldValue);
            } else {
                $parameters[] = $fieldValue;
            }
        }

        $this->getEntityManager()->getConnection()->createQueryBuilder()
            ->insert($metadata->getTableName())
            ->values($columns)
            ->setParameters($parameters)
            ->execute();
    }

    /**
     * @param array $set
     * @param array $where
     */
    public function updateEntity(array $set, array $where)
    {
        $queryBuilder = $this->createQueryBuilder('entity')
            ->update($this->getEntityName(), 'entity');

        $parameterIndex = 0;

        foreach ($set as $field => $value) {
            $parameterIndex++;
            $parameterName = 'parameter' . $parameterIndex;
            $queryBuilder->set('entity.' . $field, ':' . $parameterName)
                ->setParameter($parameterName, $value);
        }

        $this->applyWhereCondition($queryBuilder, $where, $parameterIndex);

        $queryBuilder
            ->setMaxResults(1)
            ->getQuery()
            ->execute();
    }

    /**
     * @param array $where
     */
    public function deleteEntity(array $where)
    {
        $queryBuilder = $this->createQueryBuilder('entity')
            ->delete($this->getEntityName(), 'entity');

        $this->applyWhereCondition($queryBuilder, $where);

        $queryBuilder
            ->setMaxResults(1)
            ->getQuery()
            ->execute();
    }

    /**
     * @param array $where
     * @return bool
     */
    public function hasEntity(array $where)
    {
        $queryBuilder = $this->createQueryBuilder('entity')
            ->select('entity.visibility');

        $this->applyWhereCondition($queryBuilder, $where);

        return $queryBuilder
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult() !== null;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $where
     * @param int $parameterIndex
     */
    protected function applyWhereCondition(QueryBuilder $queryBuilder, array $where, $parameterIndex = 0)
    {
        foreach ($where as $field => $value) {
            $parameterIndex++;
            $parameterName = 'parameter' . $parameterIndex;
            $queryBuilder->andWhere(sprintf('entity.%s = :%s', $field, $parameterName))
                ->setParameter($parameterName, $value);
        }
    }

    /**
     * @param object $entity
     * @return int
     */
    protected function getEntityIdentifier($entity)
    {
        $fieldClass = ClassUtils::getClass($entity);
        $metadataFactory = $this->getEntityManager()->getMetadataFactory();
        if ($metadataFactory->hasMetadataFor($fieldClass)) {
            $valueMetadata = $metadataFactory->getMetadataFor($fieldClass);
            $identifiers = $valueMetadata->getIdentifierValues($entity);
            return reset($identifiers);
        } else {
            throw new \LogicException('Can\'t get metadata for inserted entity');
        }
    }
}
