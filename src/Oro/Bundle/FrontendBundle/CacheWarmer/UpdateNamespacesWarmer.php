<?php

namespace Oro\Bundle\FrontendBundle\CacheWarmer;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;

/**
 * Change namespace in all loaded migrations, fixtures and entity config data
 * It can't be done in migrations because cache warmup requires existing entities in entity config, see BAP-11101
 *
 * TODO: remove this warmer after stable release
 */
class UpdateNamespacesWarmer implements CacheWarmerInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var bool
     */
    protected $applicationInstalled;

    /**
     * @param ManagerRegistry $managerRegistry
     * @param ConfigManager $configManager
     * @param $applicationInstalled
     */
    public function __construct(ManagerRegistry $managerRegistry, ConfigManager $configManager, $applicationInstalled)
    {
        $this->managerRegistry = $managerRegistry;
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

        /** @var Connection $defaultConnection */
        $defaultConnection = $this->managerRegistry->getConnection();

        if (!$this->isUpdateRequired($defaultConnection)) {
            return; // all data already was migrated
        }

        /** @var Connection $configConnection */
        $configConnection = $this->managerRegistry->getConnection('config');
        /** @var Connection $searchConnection */
        $searchConnection = $this->managerRegistry->getConnection('search');

        $defaultConnection->beginTransaction();
        $configConnection->beginTransaction();
        $searchConnection->beginTransaction();
        try {
            $this->fixClassNames($defaultConnection, 'oro_migrations', 'bundle');
            $this->fixClassNames($defaultConnection, 'oro_migrations_data', 'class_name');
            $this->fixClassNames($defaultConnection, 'acl_classes', 'class_type');
            $this->fixClassNames($defaultConnection, 'oro_security_permission_entity', 'name');
            $this->fixClassNames($defaultConnection, 'oro_email_template', 'entityname');
            $this->fixClassNames($searchConnection, 'oro_search_item', 'entity');

            $this->updateEntityConfigTable($configConnection);
            $this->updateEntityConfigFieldTables($configConnection);

            $defaultConnection->commit();
            $configConnection->commit();
            $searchConnection->commit();
        } catch (\Exception $e) {
            $defaultConnection->rollBack();
            $configConnection->rollBack();
            $searchConnection->rollBack();
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
    protected function isUpdateRequired(Connection $defaultConnection)
    {
        try {
            $id = $defaultConnection->fetchColumn("SELECT id FROM oro_migrations WHERE bundle LIKE 'OroB2B%' LIMIT 1");
        } catch (\Exception $e) {
            return false;
        }

        return !empty($id);
    }

    /**
     * @param Connection $configConnection
     */
    protected function updateEntityConfigTable(Connection $configConnection)
    {
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

    /**
     * @param Connection $configConnection
     */
    protected function updateEntityConfigFieldTables(Connection $configConnection)
    {
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
     * @param Connection $connection
     * @param string $table
     * @param string $column
     */
    protected function fixClassNames(Connection $connection, $table, $column)
    {
        $rows = $connection->fetchAll("SELECT id, $column FROM $table WHERE $column LIKE 'OroB2B%'");
        foreach ($rows as $row) {
            $id = $row['id'];
            $className = $row[$column];
            $className = preg_replace('/^OroB2B/', 'Oro', $className, 1);
            $connection->executeQuery("UPDATE $table SET $column = ? WHERE id = ?", [$className, $id]);
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
            } elseif ($value instanceof ConfigIdInterface) {
                $originalClass = $value->getClassName();
                $alteredClass = $this->replaceStringValue($originalClass);
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
     * @param mixed $value
     * @return mixed
     */
    protected function replaceStringValue($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        return str_replace(
            ['OroB2B\\Bundle\\', 'orob2b.', 'Extend\Entity\EX_OroB2B'],
            ['Oro\\Bundle\\', 'oro.', 'Extend\Entity\EX_Oro'],
            $value
        );
    }
}
