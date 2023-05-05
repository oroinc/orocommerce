<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_20;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Migrations\Schema\OroCatalogBundleInstaller;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddCategorySortOrder implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createProductCategorySortOrder($schema);
    }

    /**
     * Adds category_sort_order field to oro_product table & related extended field
     * @param Schema $schema
     * @return void
     */
    protected function createProductCategorySortOrder(Schema $schema): void
    {
        $table = $schema->getTable(OroCatalogBundleInstaller::ORO_PRODUCT_TABLE_NAME);
        if (!$table->hasColumn('category_sort_order')) {
            $table->addColumn('category_sort_order', 'float', [
                'notnull' => false,
                'default' => null,
                'oro_options' => [
                    'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                    'importexport' => ['excluded' => true],
                    'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                    'form' => ['is_enabled' => false],
                    'email' => ['available_in_template' => false],
                    'view' => ['is_displayable' => false],
                    'merge' => ['display' => false],
                ],
            ]);
        }
    }
}
