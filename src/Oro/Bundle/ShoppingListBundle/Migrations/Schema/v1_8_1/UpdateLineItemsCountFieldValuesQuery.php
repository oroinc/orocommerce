<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Schema\v1_8_1;

use Doctrine\DBAL\Types\Type;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Psr\Log\LoggerInterface;

class UpdateLineItemsCountFieldValuesQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription(): array
    {
        $logger = new ArrayLogger();
        $this->processQueries($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger): void
    {
        $this->processQueries($logger);
    }

    /**
     * @param LoggerInterface $logger
     * @param bool $dryRun
     */
    private function processQueries(LoggerInterface $logger, bool $dryRun = false): void
    {
        $data = $this->getData($logger);
        if (!$data) {
            return;
        }

        $query = 'UPDATE oro_shopping_list SET line_items_count = ? WHERE id = ?';
        foreach ($data as $row) {
            $params = [$row['count'], $row['id']];
            $types = [Type::INTEGER, Type::INTEGER];

            $this->logQuery($logger, $query, $params, $types);
            if (!$dryRun) {
                $this->connection->executeUpdate($query, $params, $types);
            }
        }
    }

    /**
     * @param LoggerInterface $logger
     * @return array
     */
    private function getData(LoggerInterface $logger): array
    {
        $sql = 'SELECT COUNT(*) AS count, line_items.shopping_list_id AS id
            FROM (
                SELECT shopping_list_id, COUNT(id)
                FROM oro_shopping_list_line_item
                GROUP BY shopping_list_id, COALESCE(parent_product_id, product_id)
            ) AS line_items
            GROUP BY line_items.shopping_list_id';

        $this->logQuery($logger, $sql);

        return $this->connection->fetchAll($sql);
    }
}
