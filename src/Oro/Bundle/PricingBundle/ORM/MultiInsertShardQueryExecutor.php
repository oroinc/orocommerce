<?php

namespace Oro\Bundle\PricingBundle\ORM;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardWalker;

/**
 * This executor should be used only for queries that will reduce result count after each execution by itself
 * Be aware that BufferedQueryResultIterator won't work correct for such queries, because it uses SKIP, LIMIT operators
 */
class MultiInsertShardQueryExecutor extends AbstractShardQueryExecutor
{
    const BUFFER_SIZE = 200; //use the same value as iterator

    /**
     * {@inheritDoc}
     */
    public function execute($className, array $fields, QueryBuilder $selectQueryBuilder)
    {
        $insertToTableName = $this->getTableName($className, $fields, $selectQueryBuilder);
        $columns = $this->getColumns($className, $fields);
        $selectQuery = $selectQueryBuilder->getQuery();
        $selectQuery->useQueryCache(false);
        $selectQuery->setMaxResults(static::BUFFER_SIZE);
        $selectQuery->setHint(PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $this->shardManager);
        $selectQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class);

        $sql = sprintf('insert into %s (%s) values ', $insertToTableName, implode(',', $columns));
        $connection = $this->shardManager->getEntityManager()->getConnection();

        $total = 0;
        do {
            $rows = $selectQuery->getArrayResult();
            $rowsCount = 0;
            $values = [];
            $columnsCount = count($columns);
            $allTypes = [];
            $types = [];
            foreach ($rows as $row) {
                $total++;
                if (count($types) === 0) {
                    $types = $this->prepareParametersTypes($row);
                }
                $values = array_merge($values, array_values($row));
                $allTypes = array_merge($allTypes, array_values($types));
                $rowsCount++;
                if ($rowsCount % static::BUFFER_SIZE === 0) {
                    $fullSql = $sql . $this->prepareSqlPlaceholders($columnsCount, $rowsCount);
                    $connection->executeUpdate($fullSql, $values, $allTypes);
                    $rowsCount = 0;
                    unset($values);
                    unset($allTypes);
                    $values = [];
                    $allTypes = [];
                }
            }
            if ($rowsCount > 0) {
                $fullSql = $sql . $this->prepareSqlPlaceholders($columnsCount, $rowsCount);
                $connection->executeUpdate($fullSql, $values, $allTypes);
                unset($values);
                unset($allTypes);
            }
        } while (count($rows) > 0);

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
                case is_integer($value):
                    $types[] = Type::INTEGER;
                    break;
                default:
                    $types[] = Type::STRING;
            }
        }

        return $types;
    }
}
