<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Schema\v1_10;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Oro\Bundle\EntityBundle\ORM\DatabasePlatformInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;

class ConfigMigration
{
    /** @var ManagerRegistry */
    private $managerRegistry;

    /** @var ConfigManager */
    private $configManager;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param ConfigManager $configManager
     */
    public function __construct(ManagerRegistry $managerRegistry, ConfigManager $configManager)
    {
        $this->managerRegistry = $managerRegistry;
        $this->configManager = $configManager;
    }

    /**
     * @param string $className
     * @param string $from
     * @param string $to
     */
    public function migrate($className, $from, $to)
    {
        /** @var Connection $configConnection */
        $configConnection = $this->managerRegistry->getConnection('config');
        $tables = $configConnection->getSchemaManager()->listTableNames();
        if (!in_array('oro_entity_config', $tables, true)) {
            return;
        }

        $this->migrateTables($className, $from, $to);
    }

    /**
     * @param string $className
     * @param string $from
     * @param string $to
     * @throws \Exception
     */
    protected function migrateTables($className, $from, $to)
    {
        /** @var Connection $configConnection */
        $configConnection = $this->managerRegistry->getConnection('config');

        $configConnection->beginTransaction();
        try {
            $this->updateEntityConfigTable($configConnection, $from, $to, $className);
            $this->updateEntityConfigFieldTables($configConnection, $from, $to, $className);

            $configConnection->commit();
        } catch (\Exception $e) {
            $configConnection->rollBack();
            throw $e;
        }

        $this->configManager->clear();
    }

    /**
     * @param Connection $connection
     * @param string $from
     * @return string
     */
    protected function prepareFrom(Connection $connection, $from)
    {
        $from = str_replace('\\', '\\\\', $from);

        if ($connection->getDatabasePlatform()->getName() === DatabasePlatformInterface::DATABASE_MYSQL) {
            return str_replace('\\', '\\\\', $from);
        }

        return $from;
    }

    /**
     * @param Connection $configConnection
     * @param string $from
     * @param string $to
     * @param string $className
     */
    protected function updateEntityConfigTable(Connection $configConnection, $from, $to, $className)
    {
        $entity = $configConnection->fetchAssoc(
            'SELECT id, class_name, data FROM oro_entity_config WHERE class_name = ?',
            [$className]
        );
        if (empty($entity)) {
            return;
        }
        $id = $entity['id'];
        $originalClassName = $entity['class_name'];
        $originalData = $entity['data'];
        $originalData = $originalData ? $configConnection->convertToPHPValue($originalData, Type::TARRAY) : [];

        $className = $this->replaceStringValue($originalClassName, $from, $to);
        $data = $this->replaceArrayValue($originalData, $from, $to);

        if ($className !== $originalClassName || $data !== $originalData) {
            $data = $configConnection->convertToDatabaseValue($data, Type::TARRAY);

            $sql = 'UPDATE oro_entity_config SET class_name = ?, data = ? WHERE id = ?';
            $parameters = [$className, $data, $id];
            $configConnection->executeUpdate($sql, $parameters);
        }
    }

    /**
     * @param Connection $configConnection
     * @param string $from
     * @param string $to
     * @param string $className
     */
    protected function updateEntityConfigFieldTables(Connection $configConnection, $from, $to, $className)
    {
        $entity = $configConnection->fetchAssoc(
            'SELECT id, class_name, data FROM oro_entity_config WHERE class_name = ?',
            [$className]
        );
        if (!$entity) {
            return;
        }
        $id = $entity['id'];
        $fields = $configConnection->fetchAll(
            'SELECT id, data FROM oro_entity_config_field WHERE entity_id = ?',
            [$id]
        );
        foreach ($fields as $field) {
            $id = $field['id'];
            $originalData = $field['data'];
            $originalData = $originalData ? $configConnection->convertToPHPValue($originalData, Type::TARRAY) : [];

            $data = $this->replaceArrayValue($originalData, $from, $to);

            if ($data !== $originalData) {
                $data = $configConnection->convertToDatabaseValue($data, Type::TARRAY);

                $sql = 'UPDATE oro_entity_config_field SET data = ? WHERE id = ?';
                $parameters = [$data, $id];
                $configConnection->executeUpdate($sql, $parameters);
            }
        }

        $indexValues = $configConnection->fetchAll(
            "SELECT id, value FROM oro_entity_config_index_value WHERE code = 'module_name'"
        );
        foreach ($indexValues as $indexValue) {
            $id = $indexValue['id'];
            $originalValue = $indexValue['value'];

            $value = $this->replaceStringValue($originalValue, $from, $to);

            if ($value !== $originalValue) {
                $sql = 'UPDATE oro_entity_config_index_value SET value = ? WHERE id = ?';
                $parameters = [$value, $id];
                $configConnection->executeUpdate($sql, $parameters);
            }
        }
    }

    /**
     * @param array $data
     * @param string $from
     * @param string $to
     * @return array
     */
    protected function replaceArrayValue(array $data, $from, $to)
    {
        foreach ($data as $originalKey => $value) {
            $key = $this->replaceStringValue($originalKey, $from, $to);
            if ($key !== $originalKey) {
                unset($data[$originalKey]);
                $data[$key] = $value;
            }
            if (is_array($value)) {
                $data[$key] = $this->replaceArrayValue($value, $from, $to);
            } elseif (is_string($value)) {
                $replaceStringValue = $this->replaceStringValue($value, $from, $to);
                $data[$key] = $replaceStringValue;
            } elseif ($value instanceof ConfigIdInterface) {
                $originalClass = $value->getClassName();
                $alteredClass = $this->replaceStringValue($originalClass, $from, $to);
                if ($alteredClass !== $originalClass) {
                    $reflectionProperty = new \ReflectionProperty(get_class($value), 'className');
                    $reflectionProperty->setAccessible(true);
                    $reflectionProperty->setValue($value, $alteredClass);
                }
            }
        }

        return $data;
    }

    /**
     * @param string $value
     * @param string $from
     * @param string $to
     * @return string
     */
    protected function replaceStringValue($value, $from, $to)
    {
        if (!is_string($value)) {
            return $value;
        }

        return str_replace([$from], [$to], $value);
    }
}
