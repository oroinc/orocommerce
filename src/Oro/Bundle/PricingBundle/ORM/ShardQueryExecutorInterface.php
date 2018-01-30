<?php

namespace Oro\Bundle\PricingBundle\ORM;

use Doctrine\ORM\QueryBuilder;

/**
 * Interface ShardQueryExecutorInterface should be implemented with using ShardManager
 */
interface ShardQueryExecutorInterface
{
    /**
     * @param string $className
     * @param array $fields
     * @param QueryBuilder $selectQueryBuilder
     * @return int
     */
    public function execute($className, array $fields, QueryBuilder $selectQueryBuilder);
}
