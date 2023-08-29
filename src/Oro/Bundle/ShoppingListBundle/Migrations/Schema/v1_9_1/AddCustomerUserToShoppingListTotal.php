<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Schema\v1_9_1;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\ORM\DatabasePlatformInterface;
use Oro\Bundle\MigrationBundle\Migration\ConnectionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddCustomerUserToShoppingListTotal implements Migration, ConnectionAwareInterface
{
    private ?Connection $connection = null;

    public function setConnection(Connection $connection): void
    {
        $this->connection = $connection;
    }

    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->getTable('oro_shopping_list_total');
        if ($table->hasColumn('customer_user_id')) {
            return;
        }

        $this->addColumn($schema);
        $this->assignCustomerUser($queries);
        $this->updateIndex($schema);
    }

    private function addColumn(Schema $schema): void
    {
        $table = $schema->getTable('oro_shopping_list_total');
        $table->addColumn('customer_user_id', 'integer', ['notnull' => false]);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }

    private function assignCustomerUser(QueryBag $queries): void
    {
        if (DatabasePlatformInterface::DATABASE_POSTGRESQL === $this->connection->getDatabasePlatform()->getName()) {
            $sql = <<<SQL
                UPDATE oro_shopping_list_total 
                SET customer_user_id = oro_shopping_list.customer_user_id
                FROM oro_shopping_list 
                WHERE shopping_list_id = oro_shopping_list.id
            SQL;
        } else {
            $sql = <<<SQL
                UPDATE oro_shopping_list_total slt
                INNER JOIN oro_shopping_list sl ON slt.shopping_list_id = sl.id
                SET slt.customer_user_id = sl.customer_user_id
            SQL;
        }

        $queries->addPostQuery($sql);
    }

    private function updateIndex(Schema $schema): void
    {
        $table = $schema->getTable('oro_shopping_list_total');
        if ($table->hasIndex('unique_shopping_list_currency')) {
            $table->dropIndex('unique_shopping_list_currency');
            $table->addUniqueIndex(
                ['shopping_list_id', 'currency', 'customer_user_id'],
                'unique_shopping_list_currency_customer_user'
            );
        }
    }
}
