<?php

namespace Oro\Bundle\PricingBundle\ORM;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardWalker;

class InsertFromSelectShardQueryExecutor extends AbstractShardQueryExecutor
{
    /**
     * {@inheritDoc}
     */
    public function execute($className, array $fields, QueryBuilder $selectQueryBuilder)
    {
        $insertToTableName = $this->getTableName($className, $fields, $selectQueryBuilder);
        $columns = $this->getColumns($className, $fields);
        $selectQuery = $selectQueryBuilder->getQuery();
        list($params, $types) = $this->helper->processParameterMappings($selectQuery);
        $selectQuery->useQueryCache(false);
        $selectQuery->setHint(PriceShardWalker::ORO_PRICING_SHARD_MANAGER, $this->shardManager);
        $selectQuery->setHint(Query::HINT_CUSTOM_OUTPUT_WALKER, PriceShardWalker::class);

        $sql = sprintf('insert into %s (%s) %s', $insertToTableName, implode(', ', $columns), $selectQuery->getSQL());

        return $this->shardManager->getEntityManager()->getConnection()->executeUpdate($sql, $params, $types);
    }
}
