<?php

namespace Oro\Bundle\PricingBundle\ORM;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardOutputResultModifier;

/**
 * INSERT FROM SELECT shard aware pricing query executor
 */
class InsertFromSelectShardQueryExecutor extends AbstractShardQueryExecutor implements
    ShardQueryExecutorNativeSqlInterface
{
    /**
     * {@inheritDoc}
     */
    public function execute($className, array $fields, QueryBuilder $selectQueryBuilder)
    {
        $insertToTableName = $this->getTableName($className, $fields, $selectQueryBuilder);
        $selectQuery = $selectQueryBuilder->getQuery();
        [$params, $types] = $this->helper->processParameterMappings($selectQuery);
        $selectQuery->useQueryCache(false);
        $selectQuery->setHint(PriceShardOutputResultModifier::ORO_PRICING_SHARD_MANAGER, $this->shardManager);

        return $this->executeNative($insertToTableName, $className, $selectQuery->getSQL(), $fields, $params, $types);
    }

    /**
     * {@inheritDoc}
     */
    public function executeNative(
        string $insertToTableName,
        string $className,
        string $sourceSql,
        array $fields = [],
        array $params = [],
        array $types = [],
        bool $applyOnDuplicateKeyUpdate = true
    ): int {
        if ($fields) {
            $columns = $this->helper->getColumns($className, $fields);
            $sql = sprintf('insert into %s (%s) %s', $insertToTableName, implode(', ', $columns), $sourceSql);
        } else {
            $sql = sprintf('insert into %s %s', $insertToTableName, $sourceSql);
        }

        if ($applyOnDuplicateKeyUpdate) {
            $sql = $this->applyOnDuplicateKeyUpdate($className, $sql);
        }

        return $this->shardManager->getEntityManager()->getConnection()->executeStatement($sql, $params, $types);
    }
}
