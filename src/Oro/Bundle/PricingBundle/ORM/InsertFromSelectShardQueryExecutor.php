<?php

namespace Oro\Bundle\PricingBundle\ORM;

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\Expr\Select;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\EntityBundle\ORM\NativeQueryExecutorHelper;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardWalker;
use Oro\Bundle\PricingBundle\Sharding\ShardManager;

class InsertFromSelectShardQueryExecutor extends InsertFromSelectQueryExecutor
{
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
        list($params, $types) = $this->helper->processParameterMappings($selectQuery);

        $selectQuery->setHint(PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $this->shardManager);
        $selectQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class);

        $sql = sprintf('insert into %s (%s) %s', $insertToTableName, implode(', ', $columns), $selectQuery->getSQL());

        return $this->shardManager->getEntityManager()->getConnection()->executeUpdate($sql, $params, $types);
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
}
