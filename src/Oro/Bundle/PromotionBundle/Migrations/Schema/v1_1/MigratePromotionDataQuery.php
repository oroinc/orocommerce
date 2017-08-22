<?php

namespace Oro\Bundle\PromotionBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Psr\Log\LoggerInterface;

class MigratePromotionDataQuery extends ParametrizedSqlMigrationQuery
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
     * @param bool $dryRun
     */
    private function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $this->setEmptyPromotionData($logger, $dryRun);

        $this->connection->executeUpdate(
            'UPDATE oro_promotion_applied_discount SET updated_at = :updatedAt',
            ['updatedAt' => new \DateTime('now', new \DateTimeZone('UTC'))],
            ['updatedAt' => Type::DATETIME]
        );
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     * @throws \Doctrine\DBAL\DBALException
     */
    private function setEmptyPromotionData(LoggerInterface $logger, $dryRun = false)
    {
        $updateQuery = "UPDATE oro_promotion_applied_discount SET promotion_data = :promotionData";
        $params = ['promotionData' => []];
        $types = ['promotionData' => Type::JSON_ARRAY];
        $this->logQuery($logger, $updateQuery, $params, $types);

        if (!$dryRun) {
            $this->connection->executeUpdate($updateQuery, $params, $types);
        }
    }
}
