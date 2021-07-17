<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Oro\Bundle\MigrationBundle\Migration\ArrayLogger;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedMigrationQuery;
use Oro\Bundle\ProductBundle\Entity\Product;
use Psr\Log\LoggerInterface;

class UpdateCustomerVisitorLineItemsOwnerQuery extends ParametrizedMigrationQuery
{
    /**
     * {@inheritdoc}
     */
    public function getDescription(): array
    {
        $logger = new ArrayLogger();
        $this->doExecute($logger, true);

        return $logger->getMessages();
    }

    /**
     * {@inheritdoc}
     */
    public function execute(LoggerInterface $logger): void
    {
        $this->doExecute($logger);
    }

    private function doExecute(LoggerInterface $logger, bool $dryRun = false): void
    {
        $data = $this->getData($logger);
        if (!$data) {
            return;
        }

        $types = [Connection::PARAM_INT_ARRAY];
        $query = 'UPDATE oro_shopping_list_line_item SET user_owner_id = NULL WHERE id in (?)';

        foreach (array_chunk($data, 500) as $chunk) {
            $params = [$chunk];

            $this->logQuery($logger, $query, $params, $types);
            if (!$dryRun) {
                $this->connection->executeStatement($query, $params, $types);
            }
        }
    }

    private function getData(LoggerInterface $logger): array
    {
        $query = 'SELECT li.id
            FROM oro_shopping_list_line_item AS li
            INNER JOIN oro_product p on li.product_id = p.id
            WHERE li.customer_user_id IS NULL
                AND li.user_owner_id IS NOT NULL
                AND li.quantity = ?
                AND p.type = ?';

        $params = [0, Product::TYPE_CONFIGURABLE];
        $types = [Types::INTEGER, Types::STRING];

        $this->logQuery($logger, $query, $params, $types);

        return array_column($this->connection->fetchAll($query, $params, $types), 'id');
    }
}
