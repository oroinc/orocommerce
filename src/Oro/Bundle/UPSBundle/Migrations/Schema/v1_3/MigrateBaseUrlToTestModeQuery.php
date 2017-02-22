<?php

namespace Oro\Bundle\UPSBundle\Migrations\Schema\v1_3;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MigrateBaseUrlToTestModeQuery extends ParametrizedMigrationQuery
{
    const PRODUCTION_URL_PARAMETER = 'oro_ups.api.url.production';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
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

        $productionUrl = $this->container->getParameter(self::PRODUCTION_URL_PARAMETER);

        $parameters = [
            $productionUrl,
            rtrim($productionUrl, '/') . '/'
        ];

        $this->logQuery($logger, $query, $parameters);
        if (!$dryRun) {
            $this->connection->executeUpdate($query, $parameters);
        }
    }
}
