<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_14;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Migrations\Schema\OroProductBundleInstaller;

class AddIndicesToProductTables implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addProductIndices($schema);
    }

    protected function addProductIndices(Schema $schema)
    {
        $table = $schema->getTable(OroProductBundleInstaller::PRODUCT_TABLE_NAME);
        $table->dropIndex('idx_oro_product_is_featured');
        $table->addIndex(
            ['is_featured'],
            'idx_oro_product_featured',
            [],
            ['where' => '(is_featured = true)']
        );
        $table->addIndex(['id', 'updated_at'], 'idx_oro_product_id_updated_at');
        $table->addIndex(
            ['is_new_arrival'],
            'idx_oro_product_new_arrival',
            [],
            ['where' => '(is_new_arrival = true)']
        );
        $table->addIndex(['status'], 'idx_oro_product_status');
    }
}
