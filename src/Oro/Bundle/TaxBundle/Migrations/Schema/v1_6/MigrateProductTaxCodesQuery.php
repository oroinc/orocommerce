<?php

namespace Oro\Bundle\TaxBundle\Migrations\Schema\v1_6;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class MigrateProductTaxCodesQuery extends ParametrizedMigrationQuery
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
UPDATE oro_product 
SET taxCode_id = :code 
WHERE id IN (SELECT product_id FROM oro_tax_prod_tax_code_prod WHERE product_tax_code_id = :code)
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
        $query = 'SELECT id FROM oro_tax_product_tax_code;';

        $this->logQuery($logger, $query);

        $taxCodes = $this->connection->fetchAll($query);

        return $taxCodes ?: [];
    }
}
