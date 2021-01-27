<?php

namespace Oro\Bundle\ShippingBundle\Migrations\Schema\v1_3;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Psr\Log\LoggerInterface;

class ExportDataQuery extends ParametrizedSqlMigrationQuery
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
        $this->movePostalCodes($logger, $dryRun);
        $this->moveShippingRules($logger, $dryRun);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    private function movePostalCodes(LoggerInterface $logger, $dryRun)
    {
        $sql = '
            SELECT id, postal_code
            FROM oro_shipping_rule_destination
        ';
        $this->logQuery($logger, $sql);

        $destinations = $this->connection->query($sql);
        while ($row = $destinations->fetch()) {
            $postalCodes = explode(',', $row['postal_code']);

            foreach ($postalCodes as $postalCode) {
                $query = '
                    INSERT INTO oro_ship_method_postal_code(name, destination_id)
                    VALUES (:name, :destination_id)
                ';
                $params = ['name' => trim($postalCode), 'destination_id' => $row['id']];
                $types = ['name' => \PDO::PARAM_STR, 'destination_id' => \PDO::PARAM_INT];

                $this->logQuery($logger, $query, $params, $types);

                if (!$dryRun) {
                    $this->connection->executeStatement($query, $params, $types);
                }
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    private function moveShippingRules(LoggerInterface $logger, $dryRun)
    {
        $sql = '
            SELECT id, name, enabled, priority, conditions, currency, stop_processing, created_at, updated_at
            FROM oro_shipping_rule
        ';
        $this->logQuery($logger, $sql);

        $shippingRules = $this->connection->query($sql);
        while ($row = $shippingRules->fetch()) {
            $query = '
                INSERT INTO oro_rule (name, enabled, sort_order, stop_processing, expression, created_at, updated_at)
                VALUES (:name, :enabled, :sort_order, :stop_processing, :expression, :created_at, :updated_at)
            ';

            $params = [
                'name' => $row['name'],
                'enabled' => $row['enabled'],
                'sort_order' => $row['priority'],
                'stop_processing' => $row['stop_processing'],
                'expression' => $row['conditions'].'',
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'],
            ];
            $types = [
                'name' => \PDO::PARAM_STR,
                'enabled' => \PDO::PARAM_BOOL,
                'sort_order' => \PDO::PARAM_INT,
                'stop_processing' => \PDO::PARAM_BOOL,
                'expression' => \PDO::PARAM_STR,
                'created_at' => \PDO::PARAM_STR,
                'updated_at' => \PDO::PARAM_STR,
            ];

            $this->logQuery($logger, $query, $params, $types);

            $ruleId = 0;
            if (!$dryRun) {
                $this->connection->executeStatement($query, $params, $types);
                $ruleId = $this->connection->lastInsertId(
                    $this->connection->getDatabasePlatform() instanceof PostgreSqlPlatform
                        ? 'oro_rule_id_seq'
                        : null
                );
            }

            $query = '
                INSERT INTO oro_ship_method_configs_rule(rule_id, currency)
                VALUES (:rule_id, :currency)
            ';
            $params = [
                'rule_id' => $ruleId,
                'currency' => $row['currency'],
            ];
            $types = [
                'rule_id' => \PDO::PARAM_INT,
                'currency' => \PDO::PARAM_STR,
            ];
            $this->logQuery($logger, $query, $params, $types);

            if (!$dryRun) {
                $this->connection->executeStatement($query, $params, $types);
            }
        }
    }
}
