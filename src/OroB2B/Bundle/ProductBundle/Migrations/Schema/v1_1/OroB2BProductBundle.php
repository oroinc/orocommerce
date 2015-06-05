<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

use OroB2B\Bundle\ProductBundle\Model\InventoryStatus;

class OroB2BProductBundle implements Migration
{
    const PRODUCT_TABLE_NAME = 'orob2b_product';
    const PRODUCT_UNIT_TABLE_NAME = 'orob2b_product_unit';
    const PRODUCT_UNIT_PRECISION_TABLE_NAME = 'orob2b_product_unit_precision';

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateOroB2BProductTable($schema);
    }

    /**
     * Create orob2b_product table
     *
     * @param Schema $schema
     */
    protected function updateOroB2BProductTable(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_TABLE_NAME);
        $table->addColumn('image_id', 'integer', ['notnull' => false]);
        $table->addColumn('inventory_status', 'string', [
            'length' => 255,
            'default' => InventoryStatus::IN_STOCK,
        ]);
        $table->addColumn('is_visible', 'integer', ['notnull' => false]);
        $table->addIndex(['image_id'], 'IDX_5F9796C93DA5256D', []);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_attachment_file'),
            ['image_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
