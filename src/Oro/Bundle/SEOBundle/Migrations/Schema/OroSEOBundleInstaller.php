<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroSEOBundleInstaller implements Installation, ExtendExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;

    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_9';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        $options = [
            'extend' => [
                'owner' => ExtendScope::OWNER_CUSTOM,
                'without_default' => true,
                'cascade' => ['all'],
            ],
            'form' => ['is_enabled' => false],
            'view' => ['is_displayable' => false],
            'importexport' => ['excluded' => false],
        ];

        $this->addMetaInformation($schema, 'oro_product', $options);
        $this->addMetaInformation($schema, 'oro_cms_page', $options);
        $this->addMetaInformation($schema, 'oro_web_catalog_content_node', $options);
        $this->addMetaInformation($schema, 'oro_brand', $options);

        $options['importexport']['order'] = 70;
        $this->addMetaInformation($schema, 'oro_catalog_category', $options);

        $this->createOroWebCatalogProductLimitTable($schema);
    }

    /**
     * Adds metaTitle, metaDescription and metaKeywords relations to entity.
     */
    private function addMetaInformation(Schema $schema, string $ownerTable, array $options): void
    {
        if ($schema->hasTable($ownerTable)) {
            $options['extend']['orphanRemoval'] = true;
            $options['importexport']['fallback_field'] = 'string';
            $this->addMetaInformationField($schema, $ownerTable, 'metaTitles', $options);

            $options['importexport']['fallback_field'] = 'text';

            if (isset($options['importexport']['order'])) {
                $options['importexport']['order'] += 1;
            }
            $this->addMetaInformationField($schema, $ownerTable, 'metaDescriptions', $options);

            if (isset($options['importexport']['order'])) {
                $options['importexport']['order'] += 1;
            }
            $this->addMetaInformationField($schema, $ownerTable, 'metaKeywords', $options);
        }
    }

    /**
     * Add a many-to-many relation between a given table and the table corresponding to the
     * LocalizedFallbackValue entity, with the given relation name.
     */
    private function addMetaInformationField(
        Schema $schema,
        string $ownerTable,
        string $relationName,
        array $options
    ): void {
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
            $relationName,
            'oro_fallback_localization_val',
            $targetTitleColumnNames,
            $targetDetailedColumnNames,
            $targetGridColumnNames,
            $options
        );
    }

    /**
     * Create oro_web_catalog_product_limit table
     */
    private function createOroWebCatalogProductLimitTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_web_catalog_product_limit');
        $table->addColumn('id', 'guid', ['notnull' => false]);
        $table->addColumn('product_id', 'integer');
        $table->addColumn('version', 'integer');
        $table->setPrimaryKey(['id']);
    }
}
