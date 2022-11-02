<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * Handles all migrations logic executed during installation.
 */
class OroSEOBundleInstaller implements Installation, ExtendExtensionAwareInterface
{
    const PRODUCT_TABLE_NAME = 'oro_product';
    const CATEGORY_TABLE_NAME = 'oro_catalog_category';
    const LANDING_PAGE_TABLE_NAME = 'oro_cms_page';
    const WEB_CATALOG_NODE_TABLE_NAME = 'oro_web_catalog_content_node';
    const FALLBACK_LOCALE_VALUE_TABLE_NAME = 'oro_fallback_localization_val';
    const BRAND_TABLE_NAME = 'oro_brand';

    const METAINFORMATION_TITLES = 'metaTitles';
    const METAINFORMATION_DESCRIPTIONS = 'metaDescriptions';
    const METAINFORMATION_KEYWORDS = 'metaKeywords';

    /** @var ExtendExtension */
    protected $extendExtension;

    /**
     * @inheritdoc
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * @inheritdoc
     */
    public function getMigrationVersion()
    {
        return 'v1_9';
    }

    /**
     * @inheritdoc
     */
    public function up(Schema $schema, QueryBag $queries)
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

        $this->addMetaInformation($schema, self::PRODUCT_TABLE_NAME, $options);
        $this->addMetaInformation($schema, self::LANDING_PAGE_TABLE_NAME, $options);
        $this->addMetaInformation($schema, self::WEB_CATALOG_NODE_TABLE_NAME, $options);
        $this->addMetaInformation($schema, self::BRAND_TABLE_NAME, $options);

        $options['importexport']['order'] = 70;
        $this->addMetaInformation($schema, self::CATEGORY_TABLE_NAME, $options);

        $this->createOroWebCatalogProductLimitTable($schema);
    }

    /**
     * Adds metaTitle, metaDescription and metaKeywords relations to entity.
     *
     * @param Schema $schema
     * @param string $ownerTable
     * @param array $options
     */
    private function addMetaInformation(Schema $schema, $ownerTable, array $options)
    {
        if ($schema->hasTable($ownerTable)) {
            $options['extend']['orphanRemoval'] = true;
            $options['importexport']['fallback_field'] = 'string';
            $this->addMetaInformationField($schema, $ownerTable, self::METAINFORMATION_TITLES, $options);

            $options['importexport']['fallback_field'] = 'text';

            if (isset($options['importexport']['order'])) {
                $options['importexport']['order'] += 1;
            }
            $this->addMetaInformationField($schema, $ownerTable, self::METAINFORMATION_DESCRIPTIONS, $options);

            if (isset($options['importexport']['order'])) {
                $options['importexport']['order'] += 1;
            }
            $this->addMetaInformationField($schema, $ownerTable, self::METAINFORMATION_KEYWORDS, $options);
        }
    }

    /**
     * Add a many-to-many relation between a given table and the table corresponding to the
     * LocalizedFallbackValue entity, with the given relation name.
     *
     * @param Schema $schema
     * @param string $ownerTable
     * @param string $relationName
     * @param array $options
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function addMetaInformationField(Schema $schema, $ownerTable, $relationName, array $options)
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
            self::FALLBACK_LOCALE_VALUE_TABLE_NAME,
            $targetTitleColumnNames,
            $targetDetailedColumnNames,
            $targetGridColumnNames,
            $options
        );
    }

    /**
     * Create oro_web_catalog_product_limit table
     */
    private function createOroWebCatalogProductLimitTable(Schema $schema)
    {
        $table = $schema->createTable('oro_web_catalog_product_limit');
        $table->addColumn('id', 'guid', ['notnull' => false]);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('version', 'integer', []);
        $table->setPrimaryKey(['id']);
    }
}
