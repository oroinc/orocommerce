<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\PricingBundle\PricingStrategy\MergePricesCombiningStrategy;
use Psr\Log\LoggerInterface;

class OroPriceListStrategyQuery extends ParametrizedMigrationQuery
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
        $this->updatePriceListConfigPriority($logger, $dryRun);
    }


    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function updatePriceListConfigPriority(LoggerInterface $logger, $dryRun = false)
    {
        $selectQuery = 'SELECT id, array_value FROM oro_config_value WHERE name = :name AND section = :section LIMIT 1';
        $selectQueryParameters = [
            'name' => 'price_strategy',
            'section' => 'oro_pricing'
        ];
        $selectQueryTypes = [
            'name' => Type::STRING,
            'section' => Type::STRING
        ];

        $this->logQuery($logger, $selectQuery, $selectQueryParameters, $selectQueryTypes);
        $result = $this->connection->fetchColumn($selectQuery, $selectQueryParameters, 0, $selectQueryTypes);

        $updateQuery = 'UPDATE oro_config_value SET array_value = :array_value WHERE id = :id';
        $updateQueryParameters = [
            'text_value' => MergePricesCombiningStrategy::NAME,
            'id' => $result
        ];
        $updateQueryTypes = [
            'array_value' => 'array',
            'id' => 'integer'
        ];

        $this->logQuery($logger, $updateQuery, $updateQueryParameters, $updateQueryTypes);
        if (!$dryRun) {
            $this->connection->executeUpdate($updateQuery, $updateQueryParameters, $updateQueryTypes);
        }
    }
}
