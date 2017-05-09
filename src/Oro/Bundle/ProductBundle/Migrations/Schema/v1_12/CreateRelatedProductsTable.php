<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_12;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreateRelatedProductsTable implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $table = $schema->createTable('oro_product_related_products');
        $table->addColumn('product_id', 'integer', ['notnull' => true]);
        $table->addColumn('related_product_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['product_id', 'related_product_id']);
        $table->addIndex(['product_id'], 'IDX_B0C000714584665A', []);
        $table->addIndex(['related_product_id'], 'IDX_B0C00071CF496EEA', []);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['related_product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
