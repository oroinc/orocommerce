<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Connection;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

class FillMinimalPrices implements MigrationQuery, ConnectionAwareInterface
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
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        //GROUP BY is required in case of same prices for one product
        $query = 'INSERT INTO orob2b_price_product_minimal'
            . ' (product_id, combined_price_list_id, unit_code, product_sku, quantity, value, currency) '
            . ' SELECT c.product_id, c.combined_price_list_id, MIN(c.unit_code), MIN(c.product_sku), MIN(c.quantity),'
            . ' MIN(c.value), MIN(c.currency)'
            . ' FROM orob2b_price_product_combined c'
            . ' WHERE NOT EXISTS('
            . ' SELECT id'
            . ' FROM orob2b_price_product_combined cc'
            . ' WHERE'
            . ' c.combined_price_list_id = cc.combined_price_list_id AND c.product_id = cc.product_id '
            . ' AND c.currency = cc.currency AND c.value > cc.value'
            . ' )'
            . ' GROUP BY c.product_id, c.combined_price_list_id, c.currency';
        $logger->info($query);
        if (!$dryRun) {
            $this->connection->executeQuery($query);
        }
    }
}
