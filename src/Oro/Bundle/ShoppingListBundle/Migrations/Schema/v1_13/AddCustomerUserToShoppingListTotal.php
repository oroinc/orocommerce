<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Schema\v1_13;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddCustomerUserToShoppingListTotal implements Migration
{
    #[\Override]
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
        $sql = <<<SQL
            UPDATE oro_shopping_list_total 
            SET customer_user_id = oro_shopping_list.customer_user_id
            FROM oro_shopping_list 
            WHERE shopping_list_id = oro_shopping_list.id
        SQL;
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
