<?php

namespace Oro\Bundle\PricingBundle\ORM;

use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Provide most performant implementation of ShardQueryExecutorInterface
 */
class QueryExecutorProvider implements QueryExecutorProviderInterface
{
    private ManagerRegistry $registry;
    private InsertFromSelectShardQueryExecutor $insertFromSelectExecutor;
    private MultiInsertShardQueryExecutor $multiInsertQueryExecutor;
    private bool $allowInsertFromSelectExecutorUsage = true;

    public function __construct(
        ManagerRegistry $registry,
        InsertFromSelectShardQueryExecutor $insertFromSelectExecutor,
        MultiInsertShardQueryExecutor $multiInsertQueryExecutor
    ) {
        $this->registry = $registry;
        $this->insertFromSelectExecutor = $insertFromSelectExecutor;
        $this->multiInsertQueryExecutor = $multiInsertQueryExecutor;
    }

    public function setAllowInsertFromSelectExecutorUsage(bool $allowInsertFromSelectExecutorUsage): void
    {
        $this->allowInsertFromSelectExecutorUsage = $allowInsertFromSelectExecutorUsage;
    }

    public function getQueryExecutor(): ShardQueryExecutorInterface
    {
        if ($this->allowInsertFromSelectExecutorUsage
            && $this->registry->getConnection()->getDatabasePlatform() instanceof PostgreSQL94Platform) {
            return $this->insertFromSelectExecutor;
        }

        return $this->multiInsertQueryExecutor;
    }
}
