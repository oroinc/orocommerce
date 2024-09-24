<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_28;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddCollectionSortOrder implements Migration
{
    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createCollectionSortOrderTable($schema);
        $this->addCollectionSortOrderForeignKeys($schema);
    }

    /**
     * Creates oro_product_collection_sort_order table
     */
    private function createCollectionSortOrderTable(Schema $schema): void
    {
        if (!$schema->hasTable('oro_product_collection_sort_order')) {
            $table = $schema->createTable('oro_product_collection_sort_order');
            $table->addColumn('id', 'integer', ['autoincrement' => true]);
            $table->addColumn('sort_order', 'float', ['notnull' => false, 'default' => null]);
            $table->addColumn('product_id', 'integer', ['notnull' => true]);
            $table->addColumn('segment_id', 'integer', ['notnull' => true]);
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['product_id', 'segment_id'], 'product_segment_sort_uniq_idx');
        }
    }

    /**
     * Add foreign keys to the oro_product_collection_sort_order table
     */
    private function addCollectionSortOrderForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_product_collection_sort_order');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_segment'),
            ['segment_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
