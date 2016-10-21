<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

class DropOldPageSlugsQuery implements MigrationQuery, ConnectionAwareInterface
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
        $this->removeOldSlugs($logger, $dryRun);
    }

    /**
     * @param LoggerInterface $logger
     * @param $dryRun
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function removeOldSlugs($logger, $dryRun)
    {
        $query =  'DELETE FROM oro_redirect_slug WHERE id IN (SELECT slug_id FROM oro_cms_page_to_slug);';

        $logger->info($query);

        if (!$dryRun) {
            $this->connection->executeQuery($query);
        }
    }
}
