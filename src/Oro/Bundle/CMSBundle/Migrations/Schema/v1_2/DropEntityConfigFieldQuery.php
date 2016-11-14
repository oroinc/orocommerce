<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_2;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class DropEntityConfigFieldQuery extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    private $fieldName;

    /**
     * @var string
     */
    private $className;

    /**
     * @param string $className
     * @param string $fieldName
     */
    public function __construct($className, $fieldName)
    {
        $this->fieldName = $fieldName;
        $this->className = $className;
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
        $query = <<<'SQL'
DELETE FROM oro_entity_config_field
WHERE entity_id = (SELECT id FROM oro_entity_config WHERE class_name = :class)
AND field_name = :field_name
SQL;
        $params = [
            'class' => $this->className,
            'field_name' => $this->fieldName
        ];

        $this->logQuery($logger, $query, $params);

        if (!$dryRun) {
            $this->connection->executeQuery($query, $params);
        }
    }
}
