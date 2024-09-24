<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_4;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

class FillPriceListToProduct implements MigrationQuery, ConnectionAwareInterface
{
    use ConnectionAwareTrait;

    #[\Override]
    public function getDescription()
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    #[\Override]
    public function execute(LoggerInterface $logger)
    {
        $this->doExecute($logger);
    }

    protected function doExecute(LoggerInterface $logger, bool $dryRun = false): void
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
