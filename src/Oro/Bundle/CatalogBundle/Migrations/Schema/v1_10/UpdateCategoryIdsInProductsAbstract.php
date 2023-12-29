<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_10;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\ProductBundle\Migrations\Schema\OroProductBundleInstaller;
use Psr\Log\LoggerInterface;

abstract class UpdateCategoryIdsInProductsAbstract extends ParametrizedMigrationQuery
{
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
     * @param bool            $dryRun
     */
    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $query = sprintf(
            $this->getQuery(),
            OroProductBundleInstaller::PRODUCT_TABLE_NAME,
            'category_id'
        );

        $this->executeQuery($logger, $dryRun, $query);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     * @param string $query
     * @param array $params
     * @throws \Doctrine\DBAL\DBALException
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
