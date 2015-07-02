<?php

namespace OroB2B\Bundle\ShoppingListBundle\Migrations\Schema\v1_1;

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
        /** Table altering **/
        $this->updateOroB2BShoppingListTable($schema);

        /** Add FK **/
        $this->addOroB2BShoppingListForeignKeys($schema);
    }

    /**
     * @param Schema $schema
     */
    public function updateOroB2BShoppingListTable(Schema $schema)
    {
        $table = $schema->getTable('orob2b_shopping_list');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function addOroB2BShoppingListForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_shopping_list');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
