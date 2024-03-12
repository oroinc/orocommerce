<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_20;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddCategorySortOrder implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->getTable('oro_product');
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
