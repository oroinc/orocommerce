<?php

namespace Oro\Bundle\InventoryBundle\Migrations\Schema\v1_2;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\CatalogBundle\Fallback\Provider\ParentCategoryFallbackProvider;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\Migration\AddFallbackRelationTrait;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddUpcomingOptions implements Migration, ExtendExtensionAwareInterface
{
    use AddFallbackRelationTrait;
    use ExtendExtensionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addUpcomingFieldToProduct($schema);
        $this->addUpcomingFieldToCategory($schema);
        $this->addAvailabilityDateToProduct($schema);
        $this->addAvailabilityDateToCategory($schema);
    }

    private function addUpcomingFieldToProduct(Schema $schema): void
    {
        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_product',
            UpcomingProductProvider::IS_UPCOMING,
            'oro.inventory.is_upcoming.label',
            [
                CategoryFallbackProvider::FALLBACK_ID => ['fieldName' => UpcomingProductProvider::IS_UPCOMING],
            ],
            [
                'fallback' => ['fallbackType' => EntityFallbackResolver::TYPE_BOOLEAN],
            ]
        );
    }

    private function addUpcomingFieldToCategory(Schema $schema): void
    {
        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_catalog_category',
            UpcomingProductProvider::IS_UPCOMING,
            'oro.inventory.is_upcoming.label',
            [
                ParentCategoryFallbackProvider::FALLBACK_ID => ['fieldName' => UpcomingProductProvider::IS_UPCOMING],
            ],
            [
                'fallback' => ['fallbackType' => EntityFallbackResolver::TYPE_BOOLEAN],
            ]
        );
    }

    private function addAvailabilityDateToProduct(Schema $schema): void
    {
        $table = $schema->getTable('oro_product');
        $table->addColumn(
            'availability_date',
            'datetime',
            [
                'notnull' => false,
                'comment' => '(DC2Type:datetime)',
                OroOptions::KEY => [
                    'entity' => ['label' => 'oro.inventory.availability_date.label'],
                    'extend' => [
                        'owner' => ExtendScope::OWNER_CUSTOM,
                        'is_extend' => true,
                    ],
                    'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                    'form' => ['is_enabled' => false,],
                    'view' => ['is_displayable' => false],
                    'merge' => ['display' => false],
                    'dataaudit' => ['auditable' => true],
                    'importexport' => ['full' => true]
                ],
            ]
        );
    }

    private function addAvailabilityDateToCategory(Schema $schema): void
    {
        $table = $schema->getTable('oro_catalog_category');
        $table->addColumn(
            'availability_date',
            'datetime',
            [
                'notnull' => false,
                'comment' => '(DC2Type:datetime)',
                OroOptions::KEY => [
                    'entity' => ['label' => 'oro.inventory.availability_date.label'],
                    'extend' => [
                        'owner' => ExtendScope::OWNER_CUSTOM,
                        'is_extend' => true,
                    ],
                    'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                    'form' => ['is_enabled' => false,],
                    'view' => ['is_displayable' => false],
                    'merge' => ['display' => false],
                    'dataaudit' => ['auditable' => true],
                    'importexport' => ['full' => true]
                ],
            ]
        );
    }
}
