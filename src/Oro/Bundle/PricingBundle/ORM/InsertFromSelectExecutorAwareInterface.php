<?php

namespace Oro\Bundle\PricingBundle\ORM;

interface InsertFromSelectExecutorAwareInterface
{
    public function setInsertSelectExecutor(ShardQueryExecutorInterface $queryExecutor);
}
