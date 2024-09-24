<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_10;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

abstract class UpdateCategoryIdsInProductsAbstract extends ParametrizedMigrationQuery
{
    #[\Override]
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $query = sprintf($this->getQuery(), 'oro_product', 'category_id');

        $this->executeQuery($logger, $dryRun, $query);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     * @param string $query
     * @param array $params
     */
    protected function executeQuery(LoggerInterface $logger, $dryRun, $query, $params = [])
    {
        $this->logQuery($logger, $query, $params);
        if (!$dryRun) {
            $this->connection->executeStatement($query, $params);
        }
    }

    /**
     * @return string
     */
    abstract protected function getQuery();
}
