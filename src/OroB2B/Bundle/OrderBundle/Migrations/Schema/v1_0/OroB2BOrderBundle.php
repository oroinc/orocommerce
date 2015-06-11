<?php

namespace OroB2B\Bundle\OrderBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BOrderBundle implements Migration
{
    const ORDER_TABLE_NAME = 'orob2b_order';

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BOrderTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BOrderForeignKeys($schema);
    }

    /**
     * Create orob2b_order table
     *
     * @param Schema $schema
     */
    protected function createOroB2BOrderTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORDER_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('identifier', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['identifier'], 'UNIQ_C036FF9096901F54');
        $table->addIndex(['user_owner_id'], 'IDX_C036FF909EB185F9');
        $table->addIndex(['organization_id'], 'IDX_C036FF9032C8A3DE');
        $table->addIndex(['created_at'], 'created_at_index');
    }

    /**
     * Add orob2b_order foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BOrderForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORDER_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
