<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Schema\v1_6;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Migration\UpdateEntityConfigFieldValueQuery;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroShoppingListBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateOroShoppingListLineItemTable($schema);
        $this->addOroShoppingListLineItemForeignKeys($schema);
    }

    /**
     * @param Schema $schema
     */
    protected function updateOroShoppingListLineItemTable(Schema $schema)
    {
        $table = $schema->getTable('oro_shopping_list_line_item');
        $table->addColumn('parent_product_id', 'integer', ['notnull' => false]);
    }

    /**
     * @param Schema $schema
     */
    protected function addOroShoppingListLineItemForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_shopping_list_line_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['parent_product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
