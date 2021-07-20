<?php

namespace Oro\Bundle\ShoppingListBundle\Migrations\Schema\v1_5;

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
        $this->updateOroShoppingListTable($schema);

        if (class_exists('Oro\Bundle\CheckoutBundle\Entity\CheckoutSource')) {
            $queries->addPostQuery(
                new UpdateEntityConfigFieldValueQuery(
                    'Oro\Bundle\CheckoutBundle\Entity\CheckoutSource',
                    'shoppingList',
                    'datagrid',
                    'show_filter',
                    false
                )
            );
            $queries->addPostQuery(
                new UpdateEntityConfigFieldValueQuery(
                    'Oro\Bundle\CheckoutBundle\Entity\CheckoutSource',
                    'shoppingList',
                    'datagrid',
                    'is_visible',
                    DatagridScope::IS_VISIBLE_FALSE
                )
            );
        }
    }

    /**
     * Update oro_shopping_list table
     */
    protected function updateOroShoppingListTable(Schema $schema)
    {
        $table = $schema->getTable('oro_shopping_list');
        $table->dropColumn('is_current');
    }
}
