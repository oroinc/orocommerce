<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Prepare database to use new unique constraint.
 */
class RemoveSlugDuplicatesQuery extends ParametrizedSqlMigrationQuery
{
    /**
     * @return string|string[]
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    private function doExecute(LoggerInterface $logger, bool $dryRun = false): void
    {
        $this->removeDuplicates($logger, $dryRun);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function removeDuplicates(LoggerInterface $logger, bool $dryRun): void
    {
        $duplicateIdsSQL = <<<DSQL
SELECT s2.id FROM oro_redirect_slug s1
    INNER JOIN oro_redirect_slug s2
    ON 
        s1.url_hash = s2.url_hash 
        AND s1.scopes_hash = s2.scopes_hash
        AND s1.organization_id = s2.organization_id
        AND s2.id > s1.id
DSQL;

        // Workaround for "ERROR 1093 (HY000): You can't specify target table for update in FROM clause"
        if ($this->connection->getDatabasePlatform() instanceof MySqlPlatform) {
            $duplicateIdsSQL = sprintf('SELECT t.id FROM (%s) t', $duplicateIdsSQL);
        }

        $deleteSql = sprintf(
            'DELETE FROM oro_redirect_slug WHERE id IN (%s)',
            $duplicateIdsSQL
        );

        $this->logQuery($logger, $deleteSql);
        if (!$dryRun) {
            $this->connection->executeStatement($deleteSql);
        }
    }
}
