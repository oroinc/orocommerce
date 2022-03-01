<?php

namespace Oro\Bundle\PricingBundle\ORM;

/**
 * Provide most performant implementation of ShardQueryExecutorInterface
 */
interface QueryExecutorProviderInterface
{
    public function getQueryExecutor(): ShardQueryExecutorInterface;
}
