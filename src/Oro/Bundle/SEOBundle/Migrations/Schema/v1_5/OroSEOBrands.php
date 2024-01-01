<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroSEOBrands implements Migration, ExtendExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        if ($schema->hasTable('oro_brand')) {
            $this->addMetaInformationField($schema, 'oro_brand', 'metaTitles');
            $this->addMetaInformationField($schema, 'oro_brand', 'metaDescriptions');
            $this->addMetaInformationField($schema, 'oro_brand', 'metaKeywords');
        }
    }

    /**
     * Add a many-to-many relation between a given table and the table corresponding to the
     * LocalizedFallbackValue entity, with the given relation name.
     */
    private function addMetaInformationField(Schema $schema, string $ownerTable, string $relationName): void
    {
        $targetTable = $schema->getTable($ownerTable);

        // Column names are used to show a title of target entity
        $targetTitleColumnNames = $targetTable->getPrimaryKeyColumns();
        // Column names are used to show detailed info about target entity
        $targetDetailedColumnNames = $targetTable->getPrimaryKeyColumns();
        // Column names are used to show target entity in a grid
        $targetGridColumnNames = $targetTable->getPrimaryKeyColumns();

        $this->extendExtension->addManyToManyRelation(
            $schema,
            $targetTable,
            $relationName,
            'oro_fallback_localization_val',
            $targetTitleColumnNames,
            $targetDetailedColumnNames,
            $targetGridColumnNames,
            [
                'extend' => [
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'without_default' => true,
                    'cascade' => ['all'],
                ],
                'form' => ['is_enabled' => false],
                'view' => ['is_displayable' => false],
                'importexport' => [
                    'excluded' => false,
                    'fallback_field' => 'text'
                ],
            ]
        );
    }
}
