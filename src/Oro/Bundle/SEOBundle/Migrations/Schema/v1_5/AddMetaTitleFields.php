<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddMetaTitleFields implements Migration, ExtendExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->addMetaTitlesField($schema, 'oro_product');
        $this->addMetaTitlesField($schema, 'oro_catalog_category');
        $this->addMetaTitlesField($schema, 'oro_cms_page');
        $this->addMetaTitlesField($schema, 'oro_web_catalog_content_node');
    }

    private function addMetaTitlesField(Schema $schema, string $ownerTable): void
    {
        if (!$schema->hasTable($ownerTable)) {
            return;
        }

        $targetTable = $schema->getTable($ownerTable);

        // Column names are used to show a title of target entity
        $targetTitleColumnNames = $targetTable->getPrimaryKey()->getColumns();
        // Column names are used to show detailed info about target entity
        $targetDetailedColumnNames = $targetTable->getPrimaryKey()->getColumns();
        // Column names are used to show target entity in a grid
        $targetGridColumnNames = $targetTable->getPrimaryKey()->getColumns();

        $this->extendExtension->addManyToManyRelation(
            $schema,
            $targetTable,
            'metaTitles',
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
