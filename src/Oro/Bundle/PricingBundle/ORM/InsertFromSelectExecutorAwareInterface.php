<?php

namespace Oro\Bundle\PricingBundle\ORM;

interface InsertFromSelectExecutorAwareInterface
{
    /**
     * @param ShardQueryExecutorInterface $queryExecutor
     */
    public function setInsertSelectExecutor(ShardQueryExecutorInterface $queryExecutor);
}
