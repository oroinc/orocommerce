<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_28;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ProductBundle\Migrations\Schema\OroProductBundleInstaller;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddCollectionSortOrder implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createCollectionSortOrderTable($schema);
    }

    /**
     * Creates oro_product_collection_sort_order table
     * @param Schema $schema
     * @return void
     */
    protected function createCollectionSortOrderTable(Schema $schema): void
    {
        if (!$schema->hasTable(OroProductBundleInstaller::PRODUCT_COLLECTION_SORT_ORDER_TABLE_NAME)) {
            $table = $schema->createTable(OroProductBundleInstaller::PRODUCT_COLLECTION_SORT_ORDER_TABLE_NAME);
            $table->addColumn('id', 'integer', ['autoincrement' => true]);
            $table->addColumn('sort_order', 'float', [
                'notnull' => false,
                'default' => null
            ]);
            $table->addColumn('product_id', 'integer', ['notnull' => true]);
            $table->addColumn('segment_id', 'integer', ['notnull' => true]);
            $table->addUniqueIndex(
                ['product_id', 'segment_id'],
                'product_segment_sort_uniq_idx'
            );
        }
    }
}
