<?php

namespace OroB2B\Bundle\ShoppingListBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddShoppingListTotal implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOrob2BShoppingListTotalTable($schema);
        $this->addOrob2BShoppingListTotalForeignKeys($schema);
    }

    /**
     * Create orob2b_shopping_list_total table
     *
     * @param Schema $schema
     */
    protected function createOrob2BShoppingListTotalTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_shopping_list_total');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('shopping_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('currency', 'string', ['length' => 255]);
        $table->addColumn('subtotal', 'float', []);
        $table->addColumn('is_valid', 'boolean', ['default' => '']);
        $table->addUniqueIndex(['shopping_list_id', 'currency'], 'orob2b_shopping_list_total_unq');
        $table->addIndex(['shopping_list_id'], 'idx_84d27a4b23245bf9', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_shopping_list_total foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BShoppingListTotalForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_shopping_list_total');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_shopping_list'),
            ['shopping_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
