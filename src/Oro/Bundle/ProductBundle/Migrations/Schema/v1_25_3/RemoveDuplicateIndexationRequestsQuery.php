<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_25_3;

use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class RemoveDuplicateIndexationRequestsQuery extends ParametrizedMigrationQuery
{
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    public function doExecute(LoggerInterface $logger, bool $dryRun = false): void
    {
        if ($this->connection->getDatabasePlatform() instanceof PostgreSQL94Platform) {
            $query = 'DELETE FROM oro_prod_webs_reindex_req_item a
                USING oro_prod_webs_reindex_req_item b
                WHERE a.CTID < b.CTID
                AND a.product_id = b.product_id
                AND a.website_id = b.website_id
                AND a.related_job_id = b.related_job_id';

            $this->logQuery($logger, $query);

            if (!$dryRun) {
                $this->connection->executeStatement($query);
            }
        } else {
            $createTempTable = 'CREATE TEMPORARY TABLE oro_prod_webs_reindex_req_item_temp 
                AS SELECT DISTINCT product_id, website_id, related_job_id FROM oro_prod_webs_reindex_req_item';
            $clearOrigTable = 'TRUNCATE oro_prod_webs_reindex_req_item';
            $moveUnqRecords = 'INSERT INTO oro_prod_webs_reindex_req_item (product_id, website_id, related_job_id)
                SELECT product_id, website_id, related_job_id FROM oro_prod_webs_reindex_req_item_temp';
            $dropTempTable = 'DROP TEMPORARY TABLE oro_prod_webs_reindex_req_item_temp';

            $this->logQuery($logger, $createTempTable);
            $this->logQuery($logger, $clearOrigTable);
            $this->logQuery($logger, $moveUnqRecords);
            $this->logQuery($logger, $dropTempTable);

            if (!$dryRun) {
                $this->connection->executeStatement($createTempTable);
                $this->connection->executeStatement($clearOrigTable);
                $this->connection->executeStatement($moveUnqRecords);
                $this->connection->executeStatement($dropTempTable);
            }
        }
    }
}
