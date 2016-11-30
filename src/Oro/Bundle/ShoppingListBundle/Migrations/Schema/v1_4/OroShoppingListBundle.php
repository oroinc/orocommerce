<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroShoppingListBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->updateOroShoppingListTable($schema);
        $this->updateOroShoppingListLineItemTable($schema);

        /** Foreign keys generation **/
        $this->addOroShoppingListForeignKeys($schema);
        $this->addOroShoppingListLineItemForeignKeys($schema);
    }

    /**
     * Update oro_shopping_list table
     *
     * @param Schema $schema
     */
    protected function updateOroShoppingListTable(Schema $schema)
    {
        $table = $schema->getTable('oro_shopping_list');
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
    }

    /**
     * Update oro_shopping_list_line_item table
     *
     * @param Schema $schema
     */
    protected function updateOroShoppingListLineItemTable(Schema $schema)
    {
        $table = $schema->getTable('oro_shopping_list_line_item');
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
    }

    /**
     * Add oro_shopping_list foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroShoppingListForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_shopping_list');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add oro_shopping_list_line_item foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroShoppingListLineItemForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_shopping_list_line_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
