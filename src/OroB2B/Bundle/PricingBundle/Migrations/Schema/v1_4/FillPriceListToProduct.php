<?php

namespace OroB2B\Bundle\PricingBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Connection;

use Doctrine\DBAL\DBALException;
use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;

class FillPriceListToProduct implements MigrationQuery, ConnectionAwareInterface
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
     * @throws DBALException
     */
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $query = 'INSERT INTO orob2b_price_list_to_product'
            . ' (price_list_id, product_id, is_manual)'
            . ' SELECT pp.price_list_id, pp.product_id, TRUE'
            . ' FROM orob2b_price_product pp'
            . ' GROUP BY pp.price_list_id, pp.product_id';
        $logger->info($query);
        if (!$dryRun) {
            $this->connection->executeQuery($query);
        }
    }
}
