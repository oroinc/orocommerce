<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\ORM\Driver;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\SearchBundle\Engine\Orm\DBALPersisterInterface;
use Oro\Bundle\SearchBundle\Entity\AbstractItem;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Describes the general mandatory set of operations for search operations.
 * In order to be able to use certain driver for search purposes, it must implements this interface.
 */
interface DriverInterface extends DatabaseDriverInterface, DBALPersisterInterface
{
    /**
     * Create a new QueryBuilder instance that is prepopulated for this entity name
     *
     * @param string $alias
     *
     * @return QueryBuilder $qb
     */
    public function createQueryBuilder($alias);

    /**
     * Search query by Query builder object
     *
     * @param Query $query
     *
     * @return array
     */
    public function search(Query $query);

    /**
     * Get count of records without limit parameters in query
     *
     * @param \Oro\Bundle\SearchBundle\Query\Query $query
     *
     * @return integer
     */
    public function getRecordsCount(Query $query);

    /**
     * Get aggregated data assigned based on requirements from query
     *
     * @param Query $query
     * @return array
     */
    public function getAggregatedData(Query $query);

    public function initialize(EntityManagerInterface $entityManager);

    /**
     * @param string $currentAlias
     */
    public function removeIndexByAlias($currentAlias);

    /**
     * @param string $temporaryAlias
     * @param string $currentAlias
     */
    public function renameIndexAlias($temporaryAlias, $currentAlias);

    /**
     * @param array $entityIds
     * @param string $entityClass
     * @param string|null $entityAlias
     */
    public function removeEntities(array $entityIds, $entityClass, $entityAlias = null);

    /**
     * Removes index data for given $entityClass or all classes.
     * @param string $entityClass
     */
    public function removeIndexByClass($entityClass = null);

    /**
     * @return AbstractItem
     */
    public function createItem();
}
