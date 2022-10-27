<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class DropMetaTitlesEntityConfigValuesQuery extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    private $className;

    /**
     * @var string
     */
    private $fieldName;

    /**
     * @var string
     */
    private $reverseNamePrefix;

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $reverseNamePrefix
     */
    public function __construct($className, $fieldName, $reverseNamePrefix)
    {
        $this->className = $className;
        $this->fieldName = $fieldName;
        $this->reverseNamePrefix = $reverseNamePrefix;
    }

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
        $key = sprintf('manyToMany|%s|%s|%s', $this->className, LocalizedFallbackValue::class, $this->fieldName);

        $this->deleteFieldIndex($this->className, $this->fieldName, $logger, $dryRun);
        $this->deleteField($this->className, $this->fieldName, $logger, $dryRun);
        $this->updateEntityData($this->className, $this->fieldName, $key, $logger, $dryRun);

        $mappedField = $this->reverseNamePrefix . '_' . $this->fieldName;
        $this->deleteFieldIndex(LocalizedFallbackValue::class, $mappedField, $logger, $dryRun);
        $this->deleteField(LocalizedFallbackValue::class, $mappedField, $logger, $dryRun);
        $this->updateEntityData(LocalizedFallbackValue::class, $mappedField, $key, $logger, $dryRun);
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function deleteFieldIndex($className, $fieldName, LoggerInterface $logger, $dryRun = false)
    {
        $query = <<<'SQL'
DELETE FROM oro_entity_config_index_value
WHERE field_id  = (
    SELECT id FROM oro_entity_config_field
    WHERE entity_id = (SELECT id FROM oro_entity_config WHERE class_name = :class)
    AND field_name = :field_name
)
SQL;
        $params = [
            'class' => $className,
            'field_name' => $fieldName
        ];

        $this->logQuery($logger, $query, $params);

        if (!$dryRun) {
            $this->connection->executeQuery($query, $params, ['class' => 'string', 'field_name' => 'string']);
        };
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function deleteField($className, $fieldName, LoggerInterface $logger, $dryRun = false)
    {
        $query = <<<'SQL'
DELETE FROM oro_entity_config_field
WHERE entity_id = (SELECT id FROM oro_entity_config WHERE class_name = :class)
AND field_name = :field_name
SQL;
        $params = [
            'class' => $className,
            'field_name' => $fieldName
        ];

        $this->logQuery($logger, $query, $params);

        if (!$dryRun) {
            $this->connection->executeQuery($query, $params, ['class' => 'string', 'field_name' => 'string']);
        };
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @param string $relationKey
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function updateEntityData(
        $className,
        $fieldName,
        $relationKey,
        LoggerInterface $logger,
        $dryRun = false
    ) {
        $sql = 'SELECT e.data FROM oro_entity_config as e WHERE e.class_name = ? LIMIT 1';
        $entityRow = $this->connection->fetchAssoc($sql, [$className]);
        $data = $entityRow['data'];

        $data = $data ? $this->connection->convertToPHPValue($data, Types::ARRAY) : [];

        if (isset($data['extend']['relation'][$relationKey])) {
            unset($data['extend']['relation'][$relationKey]);
        }

        if (isset($data['extend']['schema']['relation'][$fieldName])) {
            unset($data['extend']['schema']['relation'][$fieldName]);
        }

        if (isset($data['extend']['schema']['addremove'][$fieldName])) {
            unset($data['extend']['schema']['addremove'][$fieldName]);
        }

        $data = $this->connection->convertToDatabaseValue($data, Types::ARRAY);

        $query = 'UPDATE oro_entity_config SET data = :data WHERE class_name = :class';
        $params = [
            'data' => $data,
            'class' => $className
        ];

        $this->logQuery($logger, $query, $params);

        if (!$dryRun) {
            $this->connection->executeQuery($query, $params);
        };
    }
}
