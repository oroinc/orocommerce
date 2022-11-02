<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema\v1_9;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class UpdateContentVariantTypeWithExcludeSubcategories implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->updateContentVariantEntity($schema);
    }

    protected function updateContentVariantEntity(Schema $schema)
    {
        if ($schema->hasTable('oro_web_catalog_variant')) {
            $table = $schema->getTable('oro_web_catalog_variant');
            $table->addColumn(
                'exclude_subcategories',
                'boolean',
                [
                    OroOptions::KEY => [
                        ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                        'entity' => ['label' => 'oro.catalog.category.include_subcategories.label'],
                        'extend' => [
                            'is_extend' => true,
                            'owner' => ExtendScope::OWNER_CUSTOM,
                        ],
                        'datagrid' => [
                            'is_visible' => DatagridScope::IS_VISIBLE_FALSE
                        ],
                        'form' => [
                            'is_enabled' => false,
                        ],
                        'view' => ['is_displayable' => false],
                        'merge' => ['display' => false],
                        'dataaudit' => ['auditable' => true],
                    ],
                ]
            );
        }
    }
}
