<?php

namespace Oro\Bundle\CMSBundle\Migration;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Moves data of LocalizedFallbackValue from the text field to the wysiwyg field.
 */
abstract class MigrateLocalizedFallbackValueWysiwygQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription(): array
    {
        $logger = new ArrayLogger();
        $this->processQueries($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger): void
    {
        $this->processQueries($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function processQueries(LoggerInterface $logger, $dryRun = false): void
    {
        $localizedValueIds = $this->getLocalizedValueIds($logger);
        if (!$localizedValueIds) {
            return;
        }

        $query = 'UPDATE oro_fallback_localization_val SET wysiwyg = text, text = NULL WHERE id IN (?)';

        $batches = array_chunk($localizedValueIds, 500);
        foreach ($batches as $batch) {
            $parameters = [$batch];
            $types = [Connection::PARAM_INT_ARRAY];

            $this->logQuery($logger, $query, $parameters, $types);
            if (!$dryRun) {
                $this->connection->executeStatement($query, $parameters, $types);
            }
        }
    }

    abstract protected function getLocalizedValueIds(LoggerInterface $logger): array;
}
