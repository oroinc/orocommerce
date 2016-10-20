<?php

namespace Oro\Bundle\CMSBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Connection;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class DropOLdPageSlugsQuery extends ParametrizedMigrationQuery
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
     * @param $logger
     * @param $dryRun
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function removeOldSlugs($logger, $dryRun)
    {
        $ids = $this->getOldSlugIds($logger);

        $query =  'DELETE FROM oro_redirect_slug WHERE id IN ('. implode(', ', $ids) .');';

        $this->logQuery($logger, $query);

        if (!$dryRun) {
            $this->connection->executeQuery($query);
        }
    }

    /**
     * @param LoggerInterface $logger
     * @return array
     */
    protected function getOldSlugIds(LoggerInterface $logger)
    {
        $query = 'SELECT s.id FROM oro_redirect_slug s
JOIN oro_cms_page_to_slug pts ON (pts.slug_id = s.id)
JOIN oro_cms_page p ON (pts.page_id = p.id);';

        $this->logQuery($logger, $query);

        $result = $this->connection->fetchAll($query);

        return array_map(
            function ($item) {
                return $item['id'];
            },
            $result
        );
    }
}
