<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_3;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

class FillMinimalPrices implements MigrationQuery, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

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

    private function doExecute(LoggerInterface $logger, bool $dryRun = false): void
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
