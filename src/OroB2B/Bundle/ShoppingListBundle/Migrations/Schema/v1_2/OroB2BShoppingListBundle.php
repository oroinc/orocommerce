<?php

namespace OroB2B\Bundle\ShoppingListBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BShoppingListBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOrob2BShoppingListTotalTable($schema);
        $this->addOrob2BShoppingListTotalForeignKeys($schema);
        
        $this->updateShoppingListTable($schema);
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
        $table->addColumn('shopping_list_id', 'integer');
        $table->addColumn('currency', 'string', ['length' => 255]);
        $table->addColumn(
            'subtotal_value',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn('is_valid', 'boolean');
        $table->addUniqueIndex(['shopping_list_id', 'currency'], 'unique_shopping_list_currency');
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
            ['onDelete' => 'CASCADE']
        );
    }
    
    /**
     * @param Schema $schema
     */
    protected function updateShoppingListTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_shopping_list');
        $table->dropColumn('currency');
        $table->dropColumn('subtotal');
        $table->dropColumn('total');
    }
}
