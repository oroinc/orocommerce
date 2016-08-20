<?php

namespace Oro\Bundle\AlternativeCheckoutBundle\Migrations\Schema\v1_2;

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
        $rows = $this->getWorkflowItems($logger);
        foreach ($rows as $row) {
            $data = json_decode($row['data'], true);

            if (array_key_exists('edit_order_link', $data)) {
                $link = $data['edit_order_link'];
                $parts = explode('/', $link);

                $id = array_pop($parts);
                if (filter_var($id, FILTER_VALIDATE_INT)) {
                    $newId = $this->getCheckoutId($logger, $id);
                    if ($newId) {
                        $queries[] = [
                            'UPDATE oro_workflow_item SET entity_id = :entity_id WHERE id = :id',
                            ['entity_id' => $newId, 'id' => $row['id']],
                            ['entity_id' => Type::INTEGER, 'id' => Type::INTEGER]
                        ];
                    }
                }
            }
        }

        $this->executeQueries($queries, $logger, $dryRun);

        $queries = [];
        $rows = $this->getCheckouts($logger);

        foreach ($rows as $row) {
            $data = json_decode($row['data'], true);
            $data['allowed'] = $row['allowed'];
            $data['allow_request_date'] = $this->serializeDate($row['allow_request_date']);
            $data['request_approval_notes'] = $row['request_approval_notes'];
            $data['requested_for_approve'] = $row['requested_for_approve'];

            $queries[] = [
                'UPDATE oro_workflow_item SET data = :data, entity_class = :entity_class WHERE id = :id',
                [
                    'data' => json_encode($data),
                    'entity_class' => 'Oro\Bundle\CheckoutBundle\Entity\Checkout',
                    'id' => $row['workflow_item_id']
                ],
                ['data' => Type::STRING, 'entity_class' => Type::STRING, 'id' => Type::INTEGER]
            ];
        }

        $this->executeQueries($queries, $logger, $dryRun);
    }

    /**
     * @param LoggerInterface $logger
     * @return array
     */
    protected function getWorkflowItems(LoggerInterface $logger)
    {
        $sql = 'SELECT id, data FROM oro_workflow_item AS wi WHERE entity_class = :entity_class';
        $params = ['entity_class' => 'Oro\Bundle\AlternativeCheckoutBundle\Entity\AlternativeCheckout'];
        $types = ['entity_class' => Type::STRING];

        $this->logQuery($logger, $sql, $params, $types);

        return $this->connection->fetchAll($sql, $params, $types);
    }

    /**
     * @param LoggerInterface $logger
     * @param int $shoppingListId
     * @return int|null
     */
    protected function getCheckoutId(LoggerInterface $logger, $shoppingListId)
    {
        $sql = 'SELECT ac.id
                FROM orob2b_alternative_checkout AS ac
                INNER JOIN orob2b_checkout AS c ON c.id = ac.id
                INNER JOIN orob2b_checkout_source AS cs ON cs.id = c.source_id
                WHERE cs.shoppingList_id = :id
                LIMIT 1';
        $params = ['id' => $shoppingListId];
        $types  = ['id' => Type::INTEGER];

        $this->logQuery($logger, $sql, $params, $types);

        $rows = $this->connection->fetchAll($sql, $params, $types);

        return $rows ? $rows[0]['id'] : null;
    }

    /**
     * @param LoggerInterface $logger
     * @return array
     */
    protected function getCheckouts(LoggerInterface $logger)
    {
        $castType = $this->connection->getDatabasePlatform() instanceof PostgreSqlPlatform ? 'varchar' : 'char';

        $sql = 'SELECT c.allowed, c.allow_request_date, c.request_approval_notes, c.requested_for_approve,
                  wi.id AS workflow_item_id, wi.data, wi.entity_id
                FROM orob2b_alternative_checkout AS c
                INNER JOIN oro_workflow_item AS wi
                  ON CAST(c.id as %s) = CAST(wi.entity_id as %s) AND wi.entity_class = :class';
        $sql = sprintf($sql, $castType, $castType);
        $params = ['class' => 'Oro\Bundle\AlternativeCheckoutBundle\Entity\AlternativeCheckout'];
        $types = ['class' => Type::STRING];

        $this->logQuery($logger, $sql, $params, $types);

        return $this->connection->fetchAll($sql, $params, $types);
    }

    /**
     * @param string $date
     * @return string
     */
    protected function serializeDate($date)
    {
        if (!$date) {
            return null;
        }

        $date = new \DateTime($date);
        $date = serialize($date);

        return base64_encode($date);
    }

    /**
     * @param array $queries
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    protected function executeQueries(array $queries, LoggerInterface $logger, $dryRun = false)
    {
        foreach ($queries as $val) {
            $this->logQuery($logger, $val[0], $val[1], $val[2]);
            if (!$dryRun) {
                $this->connection->executeUpdate($val[0], $val[1], $val[2]);
            }
        }
    }
}
