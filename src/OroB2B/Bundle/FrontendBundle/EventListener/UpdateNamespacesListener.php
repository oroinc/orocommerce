<?php

namespace OroB2B\Bundle\FrontendBundle\EventListener;

use Symfony\Component\Console\Event\ConsoleEvent;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\InstallerBundle\Command\PlatformUpdateCommand;

/**
 * Change namespace in all loaded migrations, fixtures and entity config data
 *
 * TODO: remove this listener after stable release
 */
class UpdateNamespacesListener
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var bool
     */
    protected $applicationInstalled;

    /**
     * @param Connection $connection
     * @param bool $applicationInstalled
     */
    public function __construct(Connection $connection, $applicationInstalled)
    {
        $this->connection = $connection;
        $this->applicationInstalled = $applicationInstalled;
    }

    /**
     * Update all information related to namespaces in DB
     * It can't be done in migrations because cache warmup requires existing entities in entity config
     *
     * @param ConsoleEvent $event
     */
    public function onConsoleCommand(ConsoleEvent $event)
    {
        if (!$event->getCommand() instanceof PlatformUpdateCommand) {
            return;
        }

        if (!$this->applicationInstalled) {
            return;
        }

        if (!$this->isUpdateRequired()) {
            return; // all data already was migrated
        }

        $this->connection->beginTransaction();
        try {
            $this->updateMigrationTables();
            $this->updateEntityConfigTable();
            $this->updateEntityConfigFieldTable();
            $this->updateEntityConfigIndexValueTable();
            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();
            throw $e;
        }
    }

    /**
     * @return bool
     */
    protected function isUpdateRequired()
    {
        $id = $this->connection->fetchColumn("SELECT id FROM oro_migrations WHERE bundle LIKE 'OroB2B%' LIMIT 1");

        return !empty($id);
    }

    protected function updateMigrationTables()
    {
        $migrations = $this->connection->fetchAll(
            "SELECT id, bundle FROM oro_migrations WHERE bundle LIKE 'OroB2B%'"
        );
        foreach ($migrations as $migration) {
            $id = $migration['id'];
            $bundle = $migration['bundle'];
            $bundle = preg_replace('/^OroB2B/', 'Oro', $bundle, 1);
            $this->connection->executeQuery(
                'UPDATE oro_migrations SET bundle = ? WHERE id = ?',
                [$bundle, $id]
            );
        }

        $fixtures = $this->connection->fetchAll(
            "SELECT id, class_name FROM oro_migrations_data WHERE class_name LIKE 'OroB2B%'"
        );
        foreach ($fixtures as $fixture) {
            $id = $fixture['id'];
            $className = $fixture['class_name'];
            $className = $this->replaceStringValue($className);

            $this->connection->executeQuery(
                'UPDATE oro_migrations_data SET class_name = ? WHERE id = ?',
                [$className, $id]
            );
        }
    }

    protected function updateEntityConfigTable()
    {
        $entities = $this->connection->fetchAll('SELECT id, class_name, data FROM oro_entity_config');
        foreach ($entities as $entity) {
            $id = $entity['id'];
            $originalClassName = $entity['class_name'];
            $originalData = $entity['data'];
            $originalData = $originalData ? $this->connection->convertToPHPValue($originalData, Type::TARRAY) : [];

            $className = $this->replaceStringValue($originalClassName);
            $data = $this->replaceArrayValue($originalData);

            if ($className !== $originalClassName || $data !== $originalData) {
                $data = $this->connection->convertToDatabaseValue($data, Type::TARRAY);

                $sql = 'UPDATE oro_entity_config SET class_name = ?, data = ? WHERE id = ?';
                $parameters = [$className, $data, $id];
                $this->connection->executeUpdate($sql, $parameters);
            }
        }
    }

    protected function updateEntityConfigFieldTable()
    {
        $fields = $this->connection->fetchAll('SELECT id, data FROM oro_entity_config_field');
        foreach ($fields as $field) {
            $id = $field['id'];
            $originalData = $field['data'];
            $originalData = $originalData ? $this->connection->convertToPHPValue($originalData, Type::TARRAY) : [];

            $data = $this->replaceArrayValue($originalData);

            if ($data !== $originalData) {
                $data = $this->connection->convertToDatabaseValue($data, Type::TARRAY);

                $sql = 'UPDATE oro_entity_config_field SET data = ? WHERE id = ?';
                $parameters = [$data, $id];
                $this->connection->executeUpdate($sql, $parameters);
            }
        }
    }

    protected function updateEntityConfigIndexValueTable()
    {
        $indexValues = $this->connection->fetchAll(
            "SELECT id, value FROM oro_entity_config_index_value WHERE code = 'module_name'"
        );
        foreach ($indexValues as $indexValue) {
            $id = $indexValue['id'];
            $originalValue = $indexValue['value'];

            $value = preg_replace('/^OroB2B/', 'Oro', $originalValue, 1);

            if ($value !== $originalValue) {
                $sql = 'UPDATE oro_entity_config_index_value SET value = ? WHERE id = ?';
                $parameters = [$value, $id];
                $this->connection->executeUpdate($sql, $parameters);
            }
        }
    }

    /**
     * @param array $data
     * @return array
     */
    protected function replaceArrayValue(array $data)
    {
        foreach ($data as $originalKey => $value) {
            $key = $this->replaceStringValue($originalKey);
            if ($key !== $originalKey) {
                unset($data[$originalKey]);
                $data[$key] = $value;
            }
            if (is_array($value)) {
                $data[$key] = $this->replaceArrayValue($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->replaceStringValue($value);
            }
        }

        return $data;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    protected function replaceStringValue($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        return str_replace(
            ['OroB2B\\Bundle\\', 'orob2b.'],
            ['Oro\\Bundle\\', 'oro.'],
            $value
        );
    }
}
