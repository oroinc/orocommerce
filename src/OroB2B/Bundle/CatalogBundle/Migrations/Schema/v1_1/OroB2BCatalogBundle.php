<?php

namespace OroB2B\Bundle\CatalogBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BCatalogBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BCatalogProductCtgryTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BCatalogProductCtgryForeignKeys($schema);
    }

    /**
     * Create orob2b_catalog_product_ctgry table
     *
     * @param Schema $schema
     */
    protected function createOroB2BCatalogProductCtgryTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_catalog_product_ctgry');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('category_id', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['product_id'], 'orob2b_catalog_prod_ctgry_uidx');
        $table->addIndex(['category_id'], 'IDX_AC37E6D312469DE2', []);
    }

    /**
     * Add orob2b_catalog_product_ctgry foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BCatalogProductCtgryForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_catalog_product_ctgry');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
