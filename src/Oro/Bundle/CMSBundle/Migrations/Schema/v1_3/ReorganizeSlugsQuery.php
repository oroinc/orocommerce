<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class ReorganizeSlugsQuery extends ParametrizedMigrationQuery
{
    /**
     * @var array
     * [
     *      'page_id' => 'slug',
     * ]
     */
    protected $relations = [];

    #[\Override]
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    #[\Override]
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
        $this->prepareRelations($logger);
        $this->updateRelations($logger, $dryRun);
    }

    protected function prepareRelations(LoggerInterface $logger)
    {
        $query = 'SELECT
p.id, REVERSE(SUBSTR(REVERSE(s.url), 1, POSITION(\'/\' in SUBSTR(REVERSE(s.url), 1)) - 1)) as slug
FROM oro_cms_page p
LEFT JOIN oro_redirect_slug s ON (p.current_slug_id = s.id);';

        $this->logQuery($logger, $query);

        $rows  = $this->connection->fetchAllAssociative($query);
        foreach ($rows as $row) {
            $this->relations[$row['id']] = $row['slug'];
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function updateRelations(LoggerInterface $logger, $dryRun = false)
    {
        foreach ($this->relations as $pageId => $slug) {
            $localizationValueQuery = 'INSERT INTO oro_fallback_localization_val (string) VALUES (:values);';
            $params = ['values' => $slug];
            $types = ['values' => 'string'];

            $this->logQuery($logger, $localizationValueQuery, $params, $types);

            if (!$dryRun) {
                $this->connection->executeQuery($localizationValueQuery, $params, $types);
            }

            $localizationValueQuery = 'INSERT INTO oro_cms_page_slug_prototype (page_id, localized_value_id)
VALUES (:pageId, :localizationValueId);';

            $params = ['pageId' => $pageId, 'localizationValueId' => $this->connection->lastInsertId(
                $this->connection->getDatabasePlatform() instanceof PostgreSqlPlatform
                    ? 'oro_fallback_localization_val_id_seq'
                    : null
            )];
            $types = ['pageId' => Types::INTEGER, 'localizationValueId' => Types::INTEGER ];

            $this->logQuery($logger, $localizationValueQuery, $params, $types);

            if (!$dryRun) {
                $this->connection->executeQuery($localizationValueQuery, $params, $types);
            }
        }
    }
}
