<?php

namespace Oro\Bundle\PricingBundle\ORM;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedIdentityQueryResultIterator;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardOutputResultModifier;

/**
 * This executor should be used only for queries that will reduce result count after each execution by itself
 * Be aware that BufferedQueryResultIterator won't work correct for such queries, because it uses SKIP, LIMIT operators
 */
class MultiInsertShardQueryExecutor extends AbstractShardQueryExecutor
{
    /**
     * @var int
     */
    private $batchSize = 200; //use the same value as iterator

    /**
     * @param int $batchSize
     */
    public function setBatchSize(int $batchSize)
    {
        $this->batchSize = $batchSize;
    }

    /**
     * {@inheritDoc}
     */
    public function execute($className, array $fields, QueryBuilder $selectQueryBuilder)
    {
        $insertToTableName = $this->getTableName($className, $fields, $selectQueryBuilder);
        $columns = $this->getColumns($className, $fields);
        $selectQuery = $selectQueryBuilder->getQuery();
        $selectQuery->useQueryCache(false);
        $selectQuery->setHint(PriceShardOutputResultModifier::ORO_PRICING_SHARD_MANAGER, $this->shardManager);

        $iterator = new BufferedIdentityQueryResultIterator($selectQuery);
        $iterator->setBufferSize($this->batchSize);

        $sql = sprintf('insert into %s (%s) values ', $insertToTableName, implode(',', $columns));
        $connection = $this->shardManager->getEntityManager()->getConnection();

        $total = 0;
        $batches = [];
        $rowsCount = 0;
        $values = [];
        $columnsCount = count($columns);
        $allTypes = [];
        $types = [];
        foreach ($iterator as $row) {
            $total++;
            if (count($types) === 0) {
                $types = $this->prepareParametersTypes($row);
            }
            $values = array_merge($values, array_values($row));
            $allTypes = array_merge($allTypes, array_values($types));
            $rowsCount++;
            if ($rowsCount % $this->batchSize === 0) {
                $batches[] = [
                    $sql . $this->prepareSqlPlaceholders($columnsCount, $rowsCount),
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
            $batches[] = [
                $sql . $this->prepareSqlPlaceholders($columnsCount, $rowsCount),
                $values,
                $allTypes
            ];
            unset($values, $allTypes);
        }

        foreach ($batches as $batch) {
            $connection->executeUpdate(...$batch);
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
                    $types[] = Type::BOOLEAN;
                    break;
                case is_float($value):
                    $types[] = Type::FLOAT;
                    break;
                case is_int($value):
                    $types[] = Type::INTEGER;
                    break;
                default:
                    $types[] = Type::STRING;
            }
        }

        return $types;
    }
}
