<?php

namespace OroB2B\Bundle\CheckoutBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Types\Type;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;

class UpdateCheckoutWorkflowDataQuery extends ParametrizedMigrationQuery
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
        $rows = $this->getCheckouts($logger);

        foreach ($rows as $row) {
            $data = json_decode($row['data'], true);
            $data['order'] = $row['order_id'] ? ['id' => $row['order_id']] : null;

            $queries[] = [
                'UPDATE oro_workflow_item SET data = :data WHERE id = :id',
                ['data' => json_encode($data), 'id' => $row['workflow_item_id']],
                ['data' => Type::STRING, 'id' => Type::INTEGER]
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
    protected function getCheckouts(LoggerInterface $logger)
    {
        $castType = $this->connection->getDatabasePlatform() instanceof PostgreSqlPlatform ? 'varchar' : 'char';

        $sql = 'SELECT c.order_id, wi.id AS workflow_item_id, wi.data
                FROM orob2b_default_checkout AS c
                INNER JOIN oro_workflow_item AS wi
                  ON CAST(c.id as %s) = CAST(wi.entity_id as %s) AND wi.entity_class = :class';
        $sql = sprintf($sql, $castType, $castType);
        $params = ['class' => 'OroB2B\Bundle\CheckoutBundle\Entity\Checkout'];
        $types  = ['class' => 'string'];

        $this->logQuery($logger, $sql, $params, $types);

        return $this->connection->fetchAll($sql, $params, $types);
    }
}
