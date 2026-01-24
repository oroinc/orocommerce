<?php

namespace Oro\Bundle\PricingBundle\ORM;

/**
 * Defines the contract for objects that can be configured with an insert-from-select query executor.
 */
interface InsertFromSelectExecutorAwareInterface
{
    public function setInsertSelectExecutor(ShardQueryExecutorInterface $queryExecutor);
}
