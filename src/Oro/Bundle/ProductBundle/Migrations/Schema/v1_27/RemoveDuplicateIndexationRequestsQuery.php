<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_27;

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
    }
}
