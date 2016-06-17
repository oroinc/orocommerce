<?php

namespace OroB2B\Bundle\CatalogBundle\Migrations\Schema\v1_4;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BCatalogBundle implements Migration
{
    const ORO_B2B_CATALOG_CATEGORY_TABLE_NAME = 'orob2b_catalog_category';
    const ORO_B2B_CATEGORY_UNIT_PRECISION_TABLE_NAME = 'orob2b_category_unit_precision';
    const ORO_B2B_PRODUCT_UNIT_TABLE_NAME = 'orob2b_product_unit';


    /**
     *
     * @param Schema $schema
     * @param QueryBag $queries
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroB2BCategoryUnitPrecisionTable($schema);
        $this->updateOroB2BCategoryTable($schema);
        $this->addOroB2BCategoryUnitPrecisionForeignKeys($schema);
        $this->addOroB2BCategoryForeignKeys($schema);
    }

    /**
     *
     * @param Schema $schema
     */
    protected function createOroB2BCategoryUnitPrecisionTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_CATEGORY_UNIT_PRECISION_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('unit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('unit_precision', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['unit_code'], 'IDX_D4D5D6E4FBD3D1C2');
    }

    /**
     *
     * @param Schema $schema
     */
    protected function updateOroB2BCategoryTable(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_CATALOG_CATEGORY_TABLE_NAME);
        $table->addColumn('unit_precision_id', 'integer', ['notnull' => false]);
        $table->addUniqueIndex(['unit_precision_id']);
    }

    /**
     *
     * @param Schema $schema
     */
    protected function addOroB2BCategoryUnitPrecisionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_CATEGORY_UNIT_PRECISION_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_PRODUCT_UNIT_TABLE_NAME),
            ['unit_code'],
            ['code'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     *
     * @param Schema $schema
     */
    protected function addOroB2BCategoryForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_CATALOG_CATEGORY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_CATEGORY_UNIT_PRECISION_TABLE_NAME),
            ['unit_precision_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
