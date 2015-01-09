<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BProductBundle implements Migration
{
    const TABLE_NAME = 'orob2b_product';

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOrob2BProductTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BProductForeignKeys($schema);
    }

    /**
     * Create orob2b_product table
     *
     * @param Schema $schema
     */
    protected function createOrob2BProductTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('sku', 'string', ['length' => 255]);
        $table->addColumn('createdAt', 'datetime', []);
        $table->addColumn('updatedAt', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['sku'], 'UNIQ_5F9796C9F9038C4');
        $table->addIndex(['business_unit_owner_id'], 'IDX_5F9796C959294170', []);
        $table->addIndex(['organization_id'], 'IDX_5F9796C932C8A3DE', []);
    }

    /**
     * Add orob2b_product foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BProductForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
