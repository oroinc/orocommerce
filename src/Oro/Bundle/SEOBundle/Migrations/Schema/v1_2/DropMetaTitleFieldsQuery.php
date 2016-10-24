<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

class DropMetaTitleFieldsQuery implements MigrationQuery, ConnectionAwareInterface
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * {@inheritdoc}
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
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
        $this->removeMetaTitles($logger, $dryRun);
    }

    /**
     * @param LoggerInterface $logger
     * @param $dryRun
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function removeMetaTitles($logger, $dryRun)
    {
        $query =  'DELETE FROM oro_fallback_localization_val WHERE id IN (
SELECT localizedfallbackvalue_id FROM oro_rel_1cf73d3121a159aea3971e -- Product entity
UNION SELECT localizedfallbackvalue_id FROM oro_rel_b438191e21a159aea3971e -- Page entity
UNION SELECT localizedfallbackvalue_id FROM oro_rel_ff3a7b9721a159aea3971e -- Category entity
);';

        $logger->info($query);

        if (!$dryRun) {
            $this->connection->executeQuery($query);
        }
    }
}
