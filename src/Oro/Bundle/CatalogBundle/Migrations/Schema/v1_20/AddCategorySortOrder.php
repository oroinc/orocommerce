<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_20;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Migrations\Schema\OroCatalogBundleInstaller;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddCategorySortOrder implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createSortOrderColumn($schema);
    }

    /**
     * Adds category_sort_order field to oro_product table
     * @param Schema $schema
     * @return void
     */
    protected function createSortOrderColumn(Schema $schema): void
    {
        $table = $schema->getTable(OroCatalogBundleInstaller::ORO_PRODUCT_TABLE_NAME);
        if (!$table->hasColumn('category_sort_order')) {
            $table->addColumn('category_sort_order', 'float', [
                'notnull' => false,
                'default' => null
            ]);
        }
    }
}
