<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_19;

use Doctrine\DBAL\DBALException;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateReportQuery extends ParametrizedMigrationQuery
{
    protected static array $updatingClass = [
        Category::class => [
            'titles+Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue::string' =>
                'titles+Oro\Bundle\CatalogBundle\Entity\CategoryTitle::string',
            'shortDescriptions+Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue::text' =>
                'shortDescriptions+Oro\Bundle\CatalogBundle\Entity\CategoryShortDescription::text'
        ]
    ];

    protected static array $updatingFields = [
        'Category::titles+Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue' =>
            'Category::titles+Oro\Bundle\CatalogBundle\Entity\CategoryTitle',
        'Category::shortDescriptions+Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue' =>
            'Category::shortDescriptions+Oro\Bundle\CatalogBundle\Entity\CategoryShortDescription'
    ];

    /**
     * {@inheritdoc}
     * @throws DBALException
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     * @throws DBALException
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @throws DBALException
     */
    public function doExecute(LoggerInterface $logger, bool $dryRun = false): void
    {
        $this->migrateReport($logger, $dryRun);
        $this->migrateSegment($logger, $dryRun);
    }

    /**
     * @throws DBALException
     */
    protected function migrateReport(LoggerInterface $logger, $dryRun): void
    {
        $fetch = 'SELECT r.id, r.definition, r.entity FROM oro_report r';
        $update = 'UPDATE oro_report SET definition = :definition WHERE id = :id';

        $this->logQuery($logger, $fetch);

        $rows = $this->connection->fetchAll($fetch);
        foreach ($rows as $row) {
            $def = json_decode($row['definition'], true);
            $entity = $row['entity'];
            $updated = false;

            $def = $this->fixDefinitions($def, $entity, $updated);

            if ($updated) {
                $this->executeUpdateQuery($logger, $dryRun, $def, $row, $update);
            }
        }
    }

    /**
     * @throws DBALException
     */
    protected function migrateSegment(LoggerInterface $logger, $dryRun): void
    {
        $fetch = 'SELECT s.id, s.definition, s.entity FROM oro_segment s';
        $update = 'UPDATE oro_segment SET definition = :definition WHERE id = :id';

        $this->logQuery($logger, $fetch);

        $rows = $this->connection->fetchAll($fetch);
        foreach ($rows as $row) {
            $def = json_decode($row['definition'], true);
            $entity = $row['entity'];
            $updated = false;

            $def = $this->fixDefinitions($def, $entity, $updated);

            if ($updated) {
                $this->executeUpdateQuery($logger, $dryRun, $def, $row, $update);
            }
        }
    }

    /**
     * @throws DBALException
     */
    protected function executeUpdateQuery(LoggerInterface $logger, $dryRun, $def, $row, $query): void
    {
        $params = ['definition' => json_encode($def), 'id' => $row['id']];
        $types = ['definition' => 'text', 'id' => 'integer'];
        $this->logQuery($logger, $query, $params, $types);
        if (!$dryRun) {
            $this->connection->executeStatement($query, $params, $types);
        }
    }

    protected function fixDefinitions(array $def, string $entity, bool &$updated = false): array
    {
        if (isset($def['columns'])) {
            foreach ($def['columns'] as $key => $field) {
                $field = $this->processColumnName($entity, $field, $updated);
                $def['columns'][$key] = $field;
            }
        }

        if (isset($def['filters'])) {
            foreach ($def['filters'] as $key => $field) {
                if (isset($field['columnName'])) {
                    $field = $this->processFilterDefinition($entity, $field, $updated);
                    $def['filters'][$key] = $field;
                } elseif (is_array($field)) {
                    foreach ($def['filters'][$key] as $key2 => $field2) {
                        $field2 = $this->processFilterDefinition($entity, $field2, $updated);
                        $def['filters'][$key][$key2] = $field2;
                    }
                }
            }
        }

        return $def;
    }

    /**
     * @param string $entity
     * @param array|string $field
     * @param bool $updated
     * @return array|string
     */
    protected function processFilterDefinition(string $entity, $field, bool &$updated = false)
    {
        if (isset($field['columnName'])) {
            if (isset(self::$updatingClass[$entity][$field['columnName']])) {
                $field['columnName'] = self::$updatingClass[$entity][$field['columnName']];
                $updated = true;
            } else {
                // main class is not what we expected but filter column name is following from relationships.
                $columnName = strtr($field['columnName'], self::$updatingFields);
                $updated = $columnName !== $field['columnName'] || $updated;
                $field['columnName'] = $columnName;
            }
        }

        return $field;
    }

    /**
     * @param string $entity
     * @param string|array $field
     * @param bool $updated
     * @return array|string
     */
    protected function processColumnName(string $entity, $field, bool &$updated = false)
    {
        if (isset($field['name'])) {
            if (isset(self::$updatingClass[$entity][$field['name']])) {
                $field['name'] = self::$updatingClass[$entity][$field['name']];
                $updated = true;
            } else {
                $name = strtr($field['name'], self::$updatingFields);
                $updated = $name !== $field['name'] || $updated;
                $field['name'] = $name;
            }
        }

        return $field;
    }
}
