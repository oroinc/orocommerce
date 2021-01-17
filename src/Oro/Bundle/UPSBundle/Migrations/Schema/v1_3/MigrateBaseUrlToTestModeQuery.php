<?php

namespace Oro\Bundle\UPSBundle\Migrations\Schema\v1_3;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class MigrateBaseUrlToTestModeQuery extends ParametrizedMigrationQuery
{
    /**
     * @var string
     */
    private $productionUrl;

    /**
     * @param string $productionUrl
     */
    public function __construct($productionUrl)
    {
        $this->productionUrl = $productionUrl;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->processQueries($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritDoc}
     */
    public function execute(LoggerInterface $logger)
    {
        $this->processQueries($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool            $dryRun
     */
    private function processQueries(LoggerInterface $logger, $dryRun = false)
    {
        $query
            = 'UPDATE oro_integration_transport SET ups_test_mode = true WHERE ups_base_url != ? and ups_base_url != ?';

        $parameters = [
            $this->productionUrl,
            rtrim($this->productionUrl, '/').'/',
        ];

        $this->logQuery($logger, $query, $parameters);
        if (!$dryRun) {
            $this->connection->executeStatement($query, $parameters);
        }
    }
}
