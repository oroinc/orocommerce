<?php

namespace Oro\Bundle\PricingBundle\Migrations\Schema\v1_10;

use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\PricingBundle\DependencyInjection\Configuration;
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
        $query = 'INSERT INTO oro_config_value
    (config_id, name, section, text_value, object_value, array_value, type, created_at, updated_at)
SELECT
    c.id,
    :name,
    :section,
    :text_value,
    :object_value,
    :array_value,
    :type,
    :created_at,
    :created_at
FROM oro_config c
WHERE c.entity = :entity';

        $this->logQuery($logger, $query);
        if (!$dryRun) {
            $statement = $this->connection->prepare($query);
            $statement->bindValue(':entity', 'app', Types::STRING);
            $statement->bindValue(':name', Configuration::PRICE_LIST_STRATEGIES, Types::STRING);
            $statement->bindValue(':section', Configuration::ROOT_NODE, Types::STRING);
            $statement->bindValue(':text_value', MergePricesCombiningStrategy::NAME, Types::TEXT);
            $statement->bindValue(':object_value', null, Types::OBJECT);
            $statement->bindValue(':array_value', null, Types::ARRAY);
            $statement->bindValue(':type', 'scalar', Types::STRING);
            $now = (new \DateTime())->setTimezone(new \DateTimeZone('UTC'));
            $statement->bindValue(':created_at', $now, Types::DATETIME_MUTABLE);
            $statement->execute();
        }
    }
}
