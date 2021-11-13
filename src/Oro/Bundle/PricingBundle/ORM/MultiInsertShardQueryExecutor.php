<?php

namespace Oro\Bundle\PricingBundle\ORM;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardOutputResultModifier;

/**
 * This executor should be used only for queries that will reduce result count after each execution by itself
 * Be aware that BufferedQueryResultIterator won't work correct for such queries, because it uses SKIP, LIMIT operators
 */
class MultiInsertShardQueryExecutor extends AbstractShardQueryExecutor implements
    ShardQueryExecutorNativeSqlInterface
{
    /**
     * @var int
     */
    private $batchSize = 200; //use the same value as iterator

    public function setBatchSize(int $batchSize)
    {
        $this->batchSize = $batchSize;
    }

    public function getBatchSize(): int
    {
        return $this->batchSize;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($className, array $fields, QueryBuilder $selectQueryBuilder)
    {
        $insertToTableName = $this->getTableName($className, $fields, $selectQueryBuilder);

        $selectQuery = $selectQueryBuilder->getQuery();
        $selectQuery->useQueryCache(false);
        $selectQuery->setHint(PriceShardOutputResultModifier::ORO_PRICING_SHARD_MANAGER, $this->shardManager);

        if ($this->batchSize > 0) {
            $iterator = new BufferedIdentityQueryResultIterator($selectQuery);
            $iterator->setBufferSize($this->batchSize);
        } else {
            $iterator = $selectQuery->toIterable();
        }

        return $this->executeNativeQueryInBatches(
            $insertToTableName,
            $className,
            $iterator,
            $fields,
            true
        );
    }

    public function executeNative(
        string $insertToTableName,
        string $className,
        string $sourceSql,
        array $fields = [],
        array $params = [],
        array $types = [],
        bool $applyOnDuplicateKeyUpdate = true
    ): int {
        $connection = $this->shardManager->getEntityManager()->getConnection();

        return $this->executeNativeQueryInBatches(
            $insertToTableName,
            $className,
            $connection->executeQuery($sourceSql, $params, $types),
            $fields,
            $applyOnDuplicateKeyUpdate
        );
    }

    /**
     * @param string $insertToTableName
     * @param string $className
     * @param iterable $sourceIterator
     * @param array $fields
     * @param bool $applyOnDuplicateKeyUpdate
     * @return int
     * @throws \Doctrine\DBAL\DBALException
     */
    private function executeNativeQueryInBatches(
        string $insertToTableName,
        string $className,
        iterable $sourceIterator,
        array $fields = [],
        bool $applyOnDuplicateKeyUpdate = true
    ) {
        $columns = $this->helper->getColumns($className, $fields);
        $sql = sprintf('insert into %s (%s) values ', $insertToTableName, implode(',', $columns));

        $total = 0;
        $batches = [];
        $rowsCount = 0;
        $values = [];
        $columnsCount = count($columns);
        $allTypes = [];
        $rowDataTypes = [];
        foreach ($sourceIterator as $row) {
            $total++;
            if (count($rowDataTypes) === 0) {
                $rowDataTypes = $this->prepareParametersTypes($row);
            }
            $values = array_merge($values, array_values($row));
            $allTypes = array_merge($allTypes, array_values($rowDataTypes));
            $rowsCount++;
            if ($this->batchSize && $rowsCount % $this->batchSize === 0) {
                $batchSql = $sql . $this->prepareSqlPlaceholders($columnsCount, $rowsCount);
                if ($applyOnDuplicateKeyUpdate) {
                    $batchSql = $this->applyOnDuplicateKeyUpdate($className, $batchSql);
                }
                $batches[] = [
                    $batchSql,
                    $values,
                    $allTypes
                ];
                $rowsCount = 0;
                unset($values, $allTypes);
                $values = [];
                $allTypes = [];
            }
        }
        if ($rowsCount > 0) {
            $batchSql = $sql . $this->prepareSqlPlaceholders($columnsCount, $rowsCount);
            if ($applyOnDuplicateKeyUpdate) {
                $batchSql = $this->applyOnDuplicateKeyUpdate($className, $batchSql);
            }
            $batches[] = [
                $batchSql,
                $values,
                $allTypes
            ];
            unset($values, $allTypes);
        }

        $connection = $this->shardManager->getEntityManager()->getConnection();
        foreach ($batches as $batch) {
            $connection->executeStatement(...$batch);
        }

        return $total;
    }

    /**
     * @param int $columnsCount
     * @param int $rowsCount
     *
     * @return string
     */
    private function prepareSqlPlaceholders($columnsCount, $rowsCount)
    {
        $placeHolders = '?';
        if ($columnsCount - 1 > 0) {
            $placeHolders .= str_repeat(',' . $placeHolders, $columnsCount - 1);
        }
        $placeHolders = '(' . $placeHolders . ')';

        if ($rowsCount - 1 > 0) {
            $placeHolders .= str_repeat(',' . $placeHolders, $rowsCount - 1);
        }

        return $placeHolders;
    }

    /**
     * @param array $values
     *
     * @return array
     */
    private function prepareParametersTypes(array $values)
    {
        $types = [];
        foreach ($values as $value) {
            switch (true) {
                case is_bool($value):
                    $types[] = Types::BOOLEAN;
                    break;
                case is_float($value):
                    $types[] = Types::FLOAT;
                    break;
                case is_int($value):
                    $types[] = Types::INTEGER;
                    break;
                default:
                    $types[] = Types::STRING;
            }
        }

        return $types;
    }
}
