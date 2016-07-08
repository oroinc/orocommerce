<?php

namespace OroB2B\Bundle\FrontendBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * Change namespace in all loaded migrations, fixtures and entity config data
 * It can't be done in migrations because cache warmup requires existing entities in entity config, see BAP-11101
 *
 * TODO: remove this warmer after stable release
 */
class UpdateNamespacesWarmer implements CacheWarmerInterface
{
    /**
     * @var Connection
     */
    protected $defaultConnection;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var bool
     */
    protected $applicationInstalled;

    /**
     * @param Connection $defaultConnection
     * @param ConfigManager $configManager
     * @param $applicationInstalled
     */
    public function __construct(Connection $defaultConnection, ConfigManager $configManager, $applicationInstalled)
    {
        $this->defaultConnection = $defaultConnection;
        $this->configManager = $configManager;
        $this->applicationInstalled = $applicationInstalled;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        if (!$this->applicationInstalled) {
            return;
        }

        if (!$this->isUpdateRequired()) {
            return; // all data already was migrated
        }

        $configConnection = $this->configManager->getEntityManager()->getConnection();

        $this->defaultConnection->beginTransaction();
        $configConnection->beginTransaction();
        try {
            $this->updateMigrationTables();
            $this->updateEntityConfigTable();
            $this->updateEntityConfigFieldTable();
            $this->updateEntityConfigIndexValueTable();
            $this->defaultConnection->commit();
            $configConnection->commit();
        } catch (\Exception $e) {
            $this->defaultConnection->rollBack();
            $configConnection->rollBack();
            throw $e;
        }

        $this->configManager->clear();
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return false;
    }

    /**
     * @return bool
     */
    protected function isUpdateRequired()
    {
        $id = $this->defaultConnection->fetchColumn(
            "SELECT id FROM oro_migrations WHERE bundle LIKE 'OroB2B%' LIMIT 1"
        );

        return !empty($id);
    }

    protected function updateMigrationTables()
    {
        $migrations = $this->defaultConnection->fetchAll(
            "SELECT id, bundle FROM oro_migrations WHERE bundle LIKE 'OroB2B%'"
        );
        foreach ($migrations as $migration) {
            $id = $migration['id'];
            $bundle = $migration['bundle'];
            $bundle = preg_replace('/^OroB2B/', 'Oro', $bundle, 1);
            $this->defaultConnection->executeQuery(
                'UPDATE oro_migrations SET bundle = ? WHERE id = ?',
                [$bundle, $id]
            );
        }

        $fixtures = $this->defaultConnection->fetchAll(
            "SELECT id, class_name FROM oro_migrations_data WHERE class_name LIKE 'OroB2B%'"
        );
        foreach ($fixtures as $fixture) {
            $id = $fixture['id'];
            $className = $fixture['class_name'];
            $className = $this->replaceStringValue($className);

            $this->defaultConnection->executeQuery(
                'UPDATE oro_migrations_data SET class_name = ? WHERE id = ?',
                [$className, $id]
            );
        }
    }

    protected function updateEntityConfigTable()
    {
        $configConnection = $this->configManager->getEntityManager()->getConnection();

        $entities = $configConnection->fetchAll('SELECT id, class_name, data FROM oro_entity_config');
        foreach ($entities as $entity) {
            $id = $entity['id'];
            $originalClassName = $entity['class_name'];
            $originalData = $entity['data'];
            $originalData = $originalData ? $configConnection->convertToPHPValue($originalData, Type::TARRAY) : [];

            $className = $this->replaceStringValue($originalClassName);
            $data = $this->replaceArrayValue($originalData);

            if ($className !== $originalClassName || $data !== $originalData) {
                $data = $configConnection->convertToDatabaseValue($data, Type::TARRAY);

                $sql = 'UPDATE oro_entity_config SET class_name = ?, data = ? WHERE id = ?';
                $parameters = [$className, $data, $id];
                $configConnection->executeUpdate($sql, $parameters);
            }
        }
    }

    protected function updateEntityConfigFieldTable()
    {
        $configConnection = $this->configManager->getEntityManager()->getConnection();

        $fields = $configConnection->fetchAll('SELECT id, data FROM oro_entity_config_field');
        foreach ($fields as $field) {
            $id = $field['id'];
            $originalData = $field['data'];
            $originalData = $originalData ? $configConnection->convertToPHPValue($originalData, Type::TARRAY) : [];

            $data = $this->replaceArrayValue($originalData);

            if ($data !== $originalData) {
                $data = $configConnection->convertToDatabaseValue($data, Type::TARRAY);

                $sql = 'UPDATE oro_entity_config_field SET data = ? WHERE id = ?';
                $parameters = [$data, $id];
                $configConnection->executeUpdate($sql, $parameters);
            }
        }
    }

    protected function updateEntityConfigIndexValueTable()
    {
        $configConnection = $this->configManager->getEntityManager()->getConnection();

        $indexValues = $configConnection->fetchAll(
            "SELECT id, value FROM oro_entity_config_index_value WHERE code = 'module_name'"
        );
        foreach ($indexValues as $indexValue) {
            $id = $indexValue['id'];
            $originalValue = $indexValue['value'];

            $value = preg_replace('/^OroB2B/', 'Oro', $originalValue, 1);

            if ($value !== $originalValue) {
                $sql = 'UPDATE oro_entity_config_index_value SET value = ? WHERE id = ?';
                $parameters = [$value, $id];
                $configConnection->executeUpdate($sql, $parameters);
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
