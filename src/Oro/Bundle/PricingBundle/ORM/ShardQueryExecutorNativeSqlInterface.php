<?php

namespace Oro\Bundle\PricingBundle\ORM;

/**
 * Interface ShardQueryExecutorNativeSqlInterface should be implemented with using ShardManager
 */
interface ShardQueryExecutorNativeSqlInterface
{
    /**
     * @param string $insertToTableName
     * @param string $className
     * @param string $sourceSql
     * @param array $fields
     * @param array $params
     * @param array $types
     * @param bool $applyOnDuplicateKeyUpdate
     * @return int
     */
    public function executeNative(
        string $insertToTableName,
        string $className,
        string $sourceSql,
        array $fields = [],
        array $params = [],
        array $types = [],
        bool $applyOnDuplicateKeyUpdate = true
    ): int;
}
