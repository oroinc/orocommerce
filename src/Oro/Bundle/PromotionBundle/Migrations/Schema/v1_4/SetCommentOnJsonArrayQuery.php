<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Platforms\PostgreSQL92Platform;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

/**
 * Add comment to oro_promotion_applied.config_options and oro_promotion_applied.promotion_data fields
 * on Postgres
 */
class SetCommentOnJsonArrayQuery extends ParametrizedMigrationQuery
{
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $logger->info(
            'Set missing doctrine type hint comment on json_array field.'
        );
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
     * {@inheritdoc}
     */
    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $platform = $this->connection->getDatabasePlatform();
        if ($platform instanceof PostgreSQL92Platform) {
            $commentQueries =[
                "COMMENT ON COLUMN oro_promotion_applied.config_options IS '(DC2Type:json_array)'",
                "COMMENT ON COLUMN oro_promotion_applied.promotion_data IS '(DC2Type:json_array)'"
            ];

            foreach ($commentQueries as $commentSql) {
                $this->logQuery($logger, $commentSql);

                if (!$dryRun) {
                    $this->connection->executeStatement($commentSql);
                }
            }
        }
    }
}
