<?php

namespace Oro\Bundle\TaxBundle\Migrations\Schema\v1_6;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class MigrateCustomerTaxCodesQuery extends ParametrizedMigrationQuery
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
    protected function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $taxCodes = $this->getTaxCodes($logger);
        $sql = <<<SQL
UPDATE oro_customer 
SET taxCode_id = :code 
WHERE id IN (SELECT customer_id FROM oro_tax_cus_tax_code_cus WHERE customer_tax_code_id = :code)
SQL;
        foreach ($taxCodes as $taxCode) {
            $this->logQuery($logger, $sql);
            if (!$dryRun) {
                $this->connection->executeStatement($sql, ['code' => $taxCode['id']]);
            }
        }
        $sql = <<<SQL
UPDATE oro_customer_group 
SET taxCode_id = :code 
WHERE id IN (SELECT customer_group_id FROM oro_tax_cus_grp_tc_cus_grp WHERE customer_group_tax_code_id = :code)
SQL;
        foreach ($taxCodes as $taxCode) {
            $this->logQuery($logger, $sql);
            if (!$dryRun) {
                $this->connection->executeStatement($sql, ['code' => $taxCode['id']]);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @return array
     */
    protected function getTaxCodes(LoggerInterface $logger)
    {
        $query = 'SELECT id FROM oro_tax_customer_tax_code;';

        $this->logQuery($logger, $query);

        $taxCodes = $this->connection->fetchAll($query);

        return $taxCodes ?: [];
    }
}
