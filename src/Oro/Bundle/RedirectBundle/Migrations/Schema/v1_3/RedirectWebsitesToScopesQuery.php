<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class RedirectWebsitesToScopesQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $this->updateScopeRelations($logger, $dryRun);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function updateScopeRelations(LoggerInterface $logger, $dryRun = false)
    {
        $redirects = $this->getRedirects($logger);

        foreach ($redirects as $redirectId => $websiteId) {
            $scopeId = $this->findScopeId($websiteId, $logger);

            if ($scopeId) {
                $query = 'INSERT INTO oro_redirect_scope (redirect_id, scope_id) VALUES (:redirectId, :scopeId);';
                $params = ['redirectId' => $redirectId, 'scopeId' => $scopeId];
                $types = ['redirectId' => Types::INTEGER, 'scopeId' => Types::INTEGER ];

                $this->logQuery($logger, $query, $params, $types);

                if (!$dryRun) {
                    $this->connection->executeQuery($query, $params, $types);
                }
            } else {
                $query = 'INSERT INTO oro_scope (website_id) VALUES (:websiteId);';
                $params = ['websiteId' => $websiteId];
                $types = ['websiteId' => Types::INTEGER ];

                $this->logQuery($logger, $query, $params, $types);

                if (!$dryRun) {
                    $this->connection->executeQuery($query, $params, $types);
                }
                $redirectScopeQuery = <<<'SQL'
INSERT INTO oro_redirect_scope (redirect_id, scope_id) VALUES (:redirectId, :scopeId);
SQL;
                $redirectScopeQueryParams = [
                    'redirectId' => $redirectId,
                    'scopeId' => $this->connection->lastInsertId(
                        $this->connection->getDatabasePlatform() instanceof PostgreSqlPlatform
                            ? 'oro_scope_id_seq'
                            : null
                    )
                ];
                $redirectScopeQueryTypes = ['redirectId' => Types::INTEGER, 'scopeId' => Types::INTEGER ];

                $this->logQuery($logger, $redirectScopeQuery, $redirectScopeQueryParams, $redirectScopeQueryTypes);

                if (!$dryRun) {
                    $this->connection->executeQuery(
                        $redirectScopeQuery,
                        $redirectScopeQueryParams,
                        $redirectScopeQueryTypes
                    );
                }
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @return array
     */
    protected function getRedirects(LoggerInterface $logger)
    {
        $query = 'SELECT r.id, r.website_id FROM oro_redirect r;';

        $this->logQuery($logger, $query);

        $rows  = $this->connection->fetchAll($query);

        $redirects = [];
        foreach ($rows as $row) {
            $redirects[$row['id']] = $row['website_id'];
        }

        return $redirects;
    }

    /**
     * @param int $websiteId
     * @param LoggerInterface $logger
     * @return array
     */
    protected function findScopeId($websiteId, LoggerInterface $logger)
    {
        $schemaManager = $this->connection->getSchemaManager();
        $scopeColumns = $schemaManager->listTableColumns('oro_scope');

        $query = 'SELECT s.id FROM oro_scope s WHERE website_id = :websiteId';
        foreach ($scopeColumns as $scopeColumn) {
            $columnName = $scopeColumn->getName();
            if (!in_array($columnName, ['id', 'website_id', 'serialized_data'], true)) {
                $query .= ' AND '. $columnName . ' IS NULL ';
            }
        }

        $query .= 'LIMIT 1';

        $params = ['websiteId' => $websiteId];
        $types = ['websiteId' => Types::INTEGER ];

        $this->logQuery($logger, $query, $params, $types);

        $row = $this->connection->fetchAssoc($query, $params, $types);

        return is_array($row) && array_key_exists('id', $row) ? $row['id'] : null;
    }
}
