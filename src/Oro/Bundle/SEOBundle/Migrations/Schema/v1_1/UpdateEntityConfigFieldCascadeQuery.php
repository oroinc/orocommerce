<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateEntityConfigFieldCascadeQuery extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    protected $entityFrom;

    /**
     * @var string
     */
    protected $entityTo;

    /**
     * @var string
     */
    protected $relationType;

    /**
     * @var array
     */
    protected $fields;

    public function __construct($entityFrom, $entityTo, $relationType, array $fields)
    {
        $this->entityFrom = $entityFrom;
        $this->entityTo = $entityTo;
        $this->relationType = $relationType;
        $this->fields = $fields;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Update cascade options for meta fields on given entity';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->updateEntityConfig($logger);
    }

    /**
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function updateEntityConfig(LoggerInterface $logger)
    {
        $sql = 'SELECT id, data FROM oro_entity_config WHERE class_name = ? LIMIT 1';
        $parameters = [$this->entityFrom];
        $row = $this->connection->fetchAssoc($sql, $parameters);
        $this->logQuery($logger, $sql, $parameters);

        $id = $row['id'];
        $data = isset($row['data']) ? $this->connection->convertToPHPValue($row['data'], Types::ARRAY) : [];

        foreach ($this->fields as $field) {
            $fullRelationName = implode(
                '|',
                [$this->relationType, $this->entityFrom, $this->entityTo, $field]
            );

            if (isset($data['extend']['relation'][$fullRelationName])) {
                $data['extend']['relation'][$fullRelationName]['cascade'] = ['all'];
            }
        }

        $data = $this->connection->convertToDatabaseValue($data, Types::ARRAY);

        $sql = 'UPDATE oro_entity_config SET data = ? WHERE id = ?';
        $parameters = [$data, $id];
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);
        $this->logQuery($logger, $sql, $parameters);
    }
}
