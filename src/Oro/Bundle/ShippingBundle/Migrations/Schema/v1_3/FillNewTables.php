<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_3;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Psr\Log\LoggerInterface;

class FillNewTables extends ParametrizedSqlMigrationQuery implements OrderedMigrationInterface
{
    /**
     * @return int
     */
    public function getOrder()
    {
        return 3;
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
        $this->movePostalCodes($logger, $dryRun);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    private function movePostalCodes(LoggerInterface $logger, $dryRun)
    {
        $sql = '
            INSERT INTO oro_ship_method_postal_code(name, destination_id)
            SELECT postal_code, id
            FROM oro_shipping_rule_destination
        ';

        $this->logQuery($logger, $sql);

        if (!$dryRun) {
            $this->connection->executeUpdate($sql);
        }
    }


    /*
     * //TODO: move shipping rules
     * insert into oro_rule(name, enabled, sort_order, stop_processing, expression, created_at, updated_at)
     * select name, enabled, priority, stop_processing, conditions, created_at, updated_at
     * from oro_shipping_rule
     */
}
