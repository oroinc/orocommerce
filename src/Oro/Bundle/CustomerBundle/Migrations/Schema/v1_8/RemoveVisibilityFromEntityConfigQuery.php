<?php

namespace Oro\Bundle\CustomerBundle\Migrations\Schema\v1_8;

use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class RemoveVisibilityFromEntityConfigQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription()
    {
        return 'Remove invalid configs from oro_entity_config, after tables drop';
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $query  = "DELETE FROM oro_entity_config WHERE class_name LIKE '%ProductVisibility%'";
        $this->logQuery($logger, $query);
        $this->connection->executeQuery($query);

        $query  = "DELETE FROM oro_entity_config WHERE class_name LIKE '%CategoryVisibility%'";
        $this->logQuery($logger, $query);
        $this->connection->executeQuery($query);
    }
}
