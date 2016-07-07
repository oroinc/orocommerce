<?php

namespace OroB2B\Bundle\FrontendBundle\Migrations\Schema\v1_0;

use Psr\Log\LoggerInterface;

use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class UpdateNamespacesAndTranslationsQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->updateEntityConfig($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->updateEntityConfig($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function updateEntityConfig(LoggerInterface $logger, $dryRun = false)
    {
        if (!$this->isUpdateRequired()) {
            return;
        }

        $this->updateEntityConfigTable($logger, $dryRun);
        $this->updateEntityConfigFieldTable($logger, $dryRun);
        $this->updateEntityConfigIndexValueTable($logger, $dryRun);
    }

    /**
     * @return bool
     */
    protected function isUpdateRequired()
    {
        $id = $this->connection->fetchColumn(
            "SELECT id FROM oro_entity_config WHERE class_name LIKE 'OroB2B%' LIMIT 1"
        );

        return !empty($id);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function updateEntityConfigTable(LoggerInterface $logger, $dryRun)
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

                $this->logQuery($logger, $sql, $parameters);
                if (!$dryRun) {
                    $this->connection->executeUpdate($sql, $parameters);
                }
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function updateEntityConfigFieldTable(LoggerInterface $logger, $dryRun)
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

                $this->logQuery($logger, $sql, $parameters);
                if (!$dryRun) {
                    $this->connection->executeUpdate($sql, $parameters);
                }
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function updateEntityConfigIndexValueTable(LoggerInterface $logger, $dryRun)
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

                $this->logQuery($logger, $sql, $parameters);
                if (!$dryRun) {
                    $this->connection->executeUpdate($sql, $parameters);
                }
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
