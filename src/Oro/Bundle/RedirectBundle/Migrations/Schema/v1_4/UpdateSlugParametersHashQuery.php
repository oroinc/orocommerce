<?php

namespace Oro\Bundle\RedirectBundle\Migrations\Schema\v1_4;

use Oro\Bundle\EntityBundle\ORM\DatabaseDriverInterface;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateSlugParametersHashQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * {@inheritDoc}
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $updateQuery =
        <<<PSQL
UPDATE oro_redirect_slug 
SET parameters_hash = MD5(route_name || '|' || route_parameters || '|' || COALESCE(localization_id, 0))
PSQL;

        if ($this->connection->getDriver()->getName() == DatabaseDriverInterface::DRIVER_MYSQL) {
            $updateQuery =
            <<<MYSQL
        UPDATE oro_redirect_slug 
        SET parameters_hash = MD5(CONCAT(route_name, "|", route_parameters, "|", COALESCE(localization_id, 0)))
MYSQL;
        }


        $this->logQuery($logger, $updateQuery);
        if (!$dryRun) {
            $this->connection->executeQuery($updateQuery);
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);
        return $logger->getMessages();
    }
}
