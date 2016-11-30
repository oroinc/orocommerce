<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class UpdateFallbackEntitySystemOptionConfig extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var
     */
    protected $fieldName;

    /**
     * @var string
     */
    protected $systemOption;

    /**
     * @param $entityName
     * @param $fieldName
     * @param $systemOption
     */
    public function __construct($entityName, $fieldName, $systemOption)
    {
        $this->entityName = $entityName;
        $this->fieldName = $fieldName;
        $this->systemOption = $systemOption;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Update system option config for fallback entity field config';
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
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function updateEntityConfig(LoggerInterface $logger)
    {
        $sql        = 'SELECT f.id, f.data
            FROM oro_entity_config_field as f
            INNER JOIN oro_entity_config as e ON f.entity_id = e.id
            WHERE e.class_name = ?
            AND field_name = ?
            LIMIT 1';
        $parameters = [$this->entityName, $this->fieldName];
        $row        = $this->connection->fetchAssoc($sql, $parameters);
        $this->logQuery($logger, $sql, $parameters);

        $id = $row['id'];
        $data = isset($row['data']) ? $this->connection->convertToPHPValue($row['data'], Type::TARRAY) : [];

        $data['fallback']['fallbackList']['systemConfig']['configName'] = $this->systemOption;

        $data = $this->connection->convertToDatabaseValue($data, Type::TARRAY);

        $sql        = 'UPDATE oro_entity_config_field SET data = ? WHERE id = ?';
        $parameters = [$data, $id];
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);
        $this->logQuery($logger, $sql, $parameters);
    }
}
