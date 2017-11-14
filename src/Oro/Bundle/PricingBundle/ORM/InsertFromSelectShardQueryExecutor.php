<?php

namespace Oro\Bundle\PricingBundle\ORM;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\BatchBundle\ORM\Query\BufferedQueryResultIterator;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\EntityBundle\ORM\NativeQueryExecutorHelper;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardWalker;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

class InsertFromSelectShardQueryExecutor extends InsertFromSelectQueryExecutor
{
    const BUFFER_SIZE = 200; //use the same value as iterator

    /**
     * @var ShardManager
     */
    protected $shardManager;

    /**
     * InsertFromSelectShardQueryExecutor constructor.
     * @param NativeQueryExecutorHelper $helper
     * @param $shardManager
     */
    public function __construct(NativeQueryExecutorHelper $helper, ShardManager $shardManager)
    {
        $this->shardManager = $shardManager;
        parent::__construct($helper);
    }

    /**
     * @inheritdoc
     */
    public function execute($className, array $fields, QueryBuilder $selectQueryBuilder)
    {
        $insertToTableName = $this->getTableName($className, $fields, $selectQueryBuilder);
        $columns = $this->getColumns($className, $fields);
        $selectQuery = $selectQueryBuilder->getQuery();
        $selectQuery->useQueryCache(false);
        $selectQuery->setHint(PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $this->shardManager);
        $selectQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class);

        $sql = sprintf('insert into %s (%s) values ', $insertToTableName, implode(',', $columns));
        $iterator = new BufferedQueryResultIterator($selectQuery);
        $iterator->setBufferSize(static::BUFFER_SIZE);
        $connection = $this->shardManager->getEntityManager()->getConnection();
        $values = [];
        $rowsCount = 0;
        $columnsCount = count($columns);
        $allTypes = [];
        $types = [];
        foreach ($iterator as $row) {
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
    }

    /**
     * @param $className
     * @param array $fields
     * @param QueryBuilder $selectQueryBuilder
     * @return string
     */
    protected function getTableName($className, array $fields, QueryBuilder $selectQueryBuilder)
    {
        if (!$this->shardManager->isEntitySharded($className)) {
            return $this->helper->getTableName($className);
        }
        $index = array_search($this->shardManager->getDiscriminationField($className), $fields, true);

        $selectPart = $selectQueryBuilder->getDQLPart('select');
        /** @var Select $selectPart */
        $selectPart = $selectPart[0];
        $parts = $selectPart->getParts();
        $priceListStatement = $parts[$index];
        $position = strpos(trim($priceListStatement), ' ');
        if ($position !== false) {
            $priceListStatement = substr($priceListStatement, 0, $position + 1);
        }
        $priceListId = null;
        if (strpos(':', $priceListStatement) === 0) {
            $parameterName = substr($priceListStatement, 1);
            $priceListParameter = $selectQueryBuilder->getParameter($parameterName);
            $value = $priceListParameter->getValue();
            if ($value instanceof BasePriceList) {
                $priceListId = $value->getId();
            } else {
                $priceListId = $priceListParameter;
            }
        } elseif ($priceListStatement) {
            $priceListId = $priceListStatement;
        }

        return $this->shardManager->getEnabledShardName($className, ['priceList' => (int)$priceListId]);
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
