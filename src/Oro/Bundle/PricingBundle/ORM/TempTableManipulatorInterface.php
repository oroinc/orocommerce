<?php

namespace Oro\Bundle\PricingBundle\ORM;

use Doctrine\ORM\QueryBuilder;

/**
 * Manipulate temp tables created by a given entity table.
 */
interface TempTableManipulatorInterface
{
    /**
     * @param string $className
     * @param int|string $identifier
     */
    public function createTempTableForEntity(string $className, $identifier);

    /**
     * @param string $className
     * @param int|string $identifier
     */
    public function dropTempTableForEntity(string $className, $identifier);

    /**
     * @param string $className
     * @param int|string $identifier
     */
    public function truncateTempTableForEntity(string $className, $identifier);

    /**
     * @param string $className
     * @param int|string $identifier
     * @param array $fields
     */
    public function moveDataFromTemplateTableToEntityTable(string $className, $identifier, array $fields);

    /**
     * @param string $className
     * @param int|string $identifier
     * @param array $fields
     */
    public function copyDataFromTemplateTableToEntityTable(string $className, $identifier, array $fields);

    /**
     * @param string $className
     * @param int|string $identifier
     * @return string
     */
    public function getTempTableNameForEntity(string $className, $identifier): string;

    public function getTableNameForEntity(string $className): string;

    public function setInsertSelectExecutor(ShardQueryExecutorNativeSqlInterface $queryExecutor);

    /**
     * @param string $insertToTableName
     * @param string $className
     * @param int|string $identifier
     * @param array $fields
     * @param QueryBuilder $qb
     * @param bool $applyOnDuplicateKeyUpdate
     * @param array|null $tempTableAliases
     */
    public function insertData(
        string $insertToTableName,
        string $className,
        $identifier,
        array $fields,
        QueryBuilder $qb,
        bool $applyOnDuplicateKeyUpdate = true,
        ?array $tempTableAliases = []
    );
}
