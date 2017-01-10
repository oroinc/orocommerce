<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateNoteAssociationQuery extends ParametrizedMigrationQuery
{
    const NOTE_CLASS = 'Oro\Bundle\NoteBundle\Entity\Note';
    const NOTE_TABLE = 'oro_note';

    /**
     * @var Schema
     */
    protected $schema;

    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @var string
     */
    protected $targetClass;

    /**
     * @param Schema $schema
     */
    public function __construct(Schema $schema)
    {
        $this->schema = $schema;
    }

    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info('Update note associations.');
        $this->doExecute($logger);

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
     */
    protected function doExecute(LoggerInterface $logger)
    {
        $this->updateNoteEntityConfig($logger, $this->fieldName);
        $this->updateNoteEntityFieldConfig($this->fieldName);
    }

    /**
     * @param LoggerInterface $logger
     * @param string $fieldName
     */
    protected function updateNoteEntityConfig(LoggerInterface $logger, $fieldName)
    {
        $sql = 'SELECT id, data FROM oro_entity_config WHERE class_name = :class LIMIT 1';
        $params = ['class' => self::NOTE_CLASS];
        $types = ['class' => 'string'];

        $this->logQuery($logger, $sql, $params, $types);
        $result = $this->connection->fetchAssoc($sql, $params, $types);
        $config = $this->connection->convertToPHPValue($result['data'], 'array');
        $key = 'manyToOne|Oro\\Bundle\\NoteBundle\\Entity\\Note|Oro\\Bundle\\CustomerBundle\\Entity\\'
            . $this->targetClass . '|' . $fieldName;
        unset($config['extend']['relation'][$key]);
        unset($config['extend']['schema']['relation'][$fieldName]);
        unset($config['extend']['schema']['relation']['account_user_role_604160ea']);

        $sql = 'UPDATE oro_entity_config SET data = :data WHERE id = :id';
        $params = [
            'data' => $config,
            'id' => $result['id'],
        ];
        $types = ['data' => 'array', 'id' => 'integer'];

        $this->logQuery($logger, $sql, $params, $types);
        $this->connection->executeUpdate($sql, $params, $types);
    }

    /**
     * @param string $fieldName
     */
    protected function updateNoteEntityFieldConfig($fieldName)
    {
        $this->connection->delete(
            'oro_entity_config_field',
            ['field_name' => $fieldName]
        );
    }

    /**
     * @param string $fieldName
     */
    public function setFieldName($fieldName)
    {
        $this->fieldName = $fieldName;
    }

    /**
     * @param string $targetClass
     */
    public function setTargetClass($targetClass)
    {
        $this->targetClass = $targetClass;
    }
}
