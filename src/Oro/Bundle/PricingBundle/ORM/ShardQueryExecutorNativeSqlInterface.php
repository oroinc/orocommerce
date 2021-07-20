<?php

namespace Oro\Bundle\PricingBundle\ORM;

/**
 * Interface ShardQueryExecutorNativeSqlInterface should be implemented with using ShardManager
 */
interface ShardQueryExecutorNativeSqlInterface
{
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
