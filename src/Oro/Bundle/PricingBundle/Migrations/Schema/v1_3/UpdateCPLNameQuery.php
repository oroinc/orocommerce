<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_3;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\MigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateCPLNameQuery implements MigrationQuery, ConnectionAwareInterface
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

    private function doExecute(LoggerInterface $logger, bool $dryRun = false): void
    {
        $query  = 'UPDATE orob2b_price_list_combined SET name=MD5(name)';
        $logger->info($query);
        if (!$dryRun) {
            $this->connection->executeQuery($query);
        }
    }
}
