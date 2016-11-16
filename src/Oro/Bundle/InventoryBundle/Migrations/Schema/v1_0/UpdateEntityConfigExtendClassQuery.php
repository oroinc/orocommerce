<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class UpdateEntityConfigExtendClassQuery extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var string
     */
    protected $fromExtendClass;

    /**
     * @var string
     */
    protected $toExtendClass;


    public function __construct($entityName, $fromExtendClass, $toExtendClass)
    {
        $this->entityName = $entityName;
        $this->fromExtendClass = $fromExtendClass;
        $this->toExtendClass = $toExtendClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Update entity extend class configuration on given entity';
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
        $sql = 'SELECT id, data FROM oro_entity_config WHERE class_name = ? LIMIT 1';
        $parameters = [$this->entityName];
        $row = $this->connection->fetchAssoc($sql, $parameters);
        $this->logQuery($logger, $sql, $parameters);

        $id = $row['id'];
        $data = isset($row['data']) ? $this->connection->convertToPHPValue($row['data'], Type::TARRAY) : [];

        $data['extend']['extend_class'] = $this->toExtendClass;
        $data['extend']['schema']['entity'] = $this->toExtendClass;
        
        $extendConfig = $data['extend']['schema']['doctrine'][$this->fromExtendClass];
        unset($data['extend']['schema']['doctrine'][$this->fromExtendClass]);
        $data['extend']['schema']['doctrine'][$this->toExtendClass] = $extendConfig;

        $data = $this->connection->convertToDatabaseValue($data, Type::TARRAY);

        $sql = 'UPDATE oro_entity_config SET data = ? WHERE id = ?';
        $parameters = [$data, $id];
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);
        $this->logQuery($logger, $sql, $parameters);
    }
}
