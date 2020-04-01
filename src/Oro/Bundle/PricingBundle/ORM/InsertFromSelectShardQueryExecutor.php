<?php

namespace Oro\Bundle\PricingBundle\ORM;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PricingBundle\ORM\Walker\PriceShardOutputResultModifier;

/**
 * Shard aware Insert From Select query executor
 */
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
        $selectQuery->setHint(PriceShardOutputResultModifier::ORO_PRICING_SHARD_MANAGER, $this->shardManager);

        $sql = sprintf('insert into %s (%s) %s', $insertToTableName, implode(', ', $columns), $selectQuery->getSQL());

        return $this->shardManager->getEntityManager()->getConnection()->executeUpdate($sql, $params, $types);
    }
}
