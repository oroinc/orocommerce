<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class MoveCheckoutAddressDataQuery extends ParametrizedMigrationQuery
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
    public function doExecute(LoggerInterface $logger, $dryRun = false)
    {
        $queries = [];
        $rows = $this->getCheckoutData($logger);

        foreach ($rows as $row) {
            $queries[] = [
                'UPDATE ' . $this->getBaseTableName() . '
                  SET billing_address_id = :billing_address_id,
                    shipping_address_id = :shipping_address_id,
                    save_billing_address = :save_billing_address,
                    ship_to_billing_address = :ship_to_billing_address,
                    save_shipping_address = :save_shipping_address
                   WHERE id = :id',
                [
                    'billing_address_id' => $row['billing_address_id'],
                    'shipping_address_id' => $row['shipping_address_id'],
                    'save_billing_address' => $row['save_billing_address'],
                    'ship_to_billing_address' => $row['ship_to_billing_address'],
                    'save_shipping_address' => $row['save_shipping_address'],
                    'id' => $row['id']
                ],
                [
                    'billing_address_id' => Type::INTEGER,
                    'shipping_address_id' => Type::INTEGER,
                    'save_billing_address' => Type::BOOLEAN,
                    'ship_to_billing_address' => Type::BOOLEAN,
                    'save_shipping_address' => Type::BOOLEAN,
                    'id' => Type::INTEGER
                ]
            ];
        }

        // execute update queries
        foreach ($queries as $val) {
            $this->logQuery($logger, $val[0], $val[1], $val[2]);
            if (!$dryRun) {
                $this->connection->executeUpdate($val[0], $val[1], $val[2]);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @return array
     */
    protected function getCheckoutData(LoggerInterface $logger)
    {
        $sql = 'SELECT dc.id, dc.billing_address_id, dc.shipping_address_id, dc.save_billing_address,
                  dc.ship_to_billing_address, dc.save_shipping_address
                FROM %s AS dc
                INNER JOIN %s AS c
                  ON c.id = dc.id';
        $sql = sprintf($sql, $this->getSourceTableName(), $this->getBaseTableName());
        $params = [];
        $types  = [];

        $this->logQuery($logger, $sql, $params, $types);

        return $this->connection->fetchAll($sql, $params, $types);
    }

    /**
     * @return string
     */
    protected function getSourceTableName()
    {
        return 'orob2b_default_checkout';
    }

    /**
     * @return string
     */
    protected function getBaseTableName()
    {
        return 'orob2b_checkout';
    }
}
