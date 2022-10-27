<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Schema\v1_6_1;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Prepare database to use scopes_hash with unique constraint.
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
        $this->fillScopeHashes($logger, $dryRun);
        $this->removeDuplicates($logger, $dryRun);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    private function fillScopeHashes(LoggerInterface $logger, bool $dryRun): void
    {
        if ($this->connection->getDatabasePlatform() instanceof MySqlPlatform) {
            $updateWithScope = <<<SQL
UPDATE oro_redirect_slug slug,
    (
        SELECT
            group_concat(coalesce(oss.scope_id, '') ORDER BY oss.scope_id) as scopes,
            oss.slug_id
        FROM oro_slug_scope oss
        GROUP BY slug_id
    ) as t
SET slug.scopes_hash = md5(concat(t.scopes, ':', coalesce(slug.localization_id, '')))
WHERE slug.id = t.slug_id
SQL;

            $updateWithoutScope = <<<SQL
UPDATE oro_redirect_slug 
SET scopes_hash = md5(concat(':', coalesce(localization_id, ''))) 
WHERE scopes_hash IS NULL
SQL;
        } else {
            $updateWithScope = <<<SQL
UPDATE oro_redirect_slug slug
SET scopes_hash = md5(t.scopes || ':'|| coalesce(slug.localization_id::text, ''))
FROM (
    SELECT 
       array_to_string(array_agg(coalesce(oss.scope_id::text, '') ORDER BY oss.scope_id), ',') as scopes,
       oss.slug_id
    FROM oro_slug_scope oss
    GROUP BY slug_id
) t
WHERE slug.id = t.slug_id
SQL;

            $updateWithoutScope = <<<SQL
UPDATE oro_redirect_slug 
SET scopes_hash = md5(':' || coalesce(localization_id::text, '')) 
WHERE scopes_hash IS NULL
SQL;
        }

        $this->logQuery($logger, $updateWithScope);
        $this->logQuery($logger, $updateWithoutScope);
        if (!$dryRun) {
            $this->connection->executeStatement($updateWithScope);
            $this->connection->executeStatement($updateWithoutScope);
        }
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
        AND s1.route_name = s2.route_name 
        AND s1.parameters_hash = s2.parameters_hash 
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
