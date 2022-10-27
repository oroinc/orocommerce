<?php

namespace Oro\Bundle\TaxBundle\Migrations\Schema\v1_6_1;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Disallow cascade operations for taxCode relation
 */
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
     * @var array
     */
    protected $fields;

    /**
     * @param string $entityFrom
     * @param string $entityTo
     * @param array $fields
     */
    public function __construct($entityFrom, $entityTo, array $fields)
    {
        $this->entityFrom = $entityFrom;
        $this->entityTo = $entityTo;
        $this->fields = $fields;
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
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $sql = 'SELECT id, data FROM oro_entity_config WHERE class_name = :className LIMIT 1';
        $parameters = ['className' => $this->entityFrom];
        $types = ['className' => Types::STRING];

        $this->logQuery($logger, $sql, $parameters);

        $row = $this->connection->fetchAssoc($sql, $parameters, $types);
        $id = $row['id'];
        $data = isset($row['data']) ? $this->connection->convertToPHPValue($row['data'], Types::ARRAY) : [];

        foreach ($this->fields as $field) {
            $fullRelationName = implode(
                '|',
                [RelationType::MANY_TO_ONE, $this->entityFrom, $this->entityTo, $field]
            );

            if (isset($data['extend']['relation'][$fullRelationName])) {
                unset($data['extend']['relation'][$fullRelationName]['cascade']);
            }
        }

        $data = $this->connection->convertToDatabaseValue($data, Types::ARRAY);

        $sql = 'UPDATE oro_entity_config SET data = ? WHERE id = ?';
        $parameters = [$data, $id];
        $this->logQuery($logger, $sql, $parameters);

        if (!$dryRun) {
            $statement = $this->connection->prepare($sql);
            $statement->execute($parameters);
        }
    }
}
