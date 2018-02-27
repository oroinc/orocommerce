<?php

namespace Oro\Bundle\SEOBundle\Migrations\Schema\v1_5;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class AddMetaTitleFields implements Migration, ExtendExtensionAwareInterface
{
    const PRODUCT_TABLE_NAME = 'oro_product';
    const CATEGORY_TABLE_NAME = 'oro_catalog_category';
    const LANDING_PAGE_TABLE_NAME = 'oro_cms_page';
    const WEB_CATALOG_NODE_TABLE_NAME = 'oro_web_catalog_content_node';
    const FALLBACK_LOCALE_VALUE_TABLE_NAME = 'oro_fallback_localization_val';

    const METAINFORMATION_TITLES = 'metaTitles';

    /**
     * @var ExtendExtension
     */
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
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addMetaInformationField($schema, self::PRODUCT_TABLE_NAME, self::METAINFORMATION_TITLES);
        $this->addMetaInformationField($schema, self::CATEGORY_TABLE_NAME, self::METAINFORMATION_TITLES);
        $this->addMetaInformationField($schema, self::LANDING_PAGE_TABLE_NAME, self::METAINFORMATION_TITLES);
        $this->addMetaInformationField($schema, self::WEB_CATALOG_NODE_TABLE_NAME, self::METAINFORMATION_TITLES);
    }

    /**
     * Add a many-to-many relation between a given table and the table corresponding to the
     * LocalizedFallbackValue entity, with the given relation name.
     *
     * @param Schema $schema
     * @param string $ownerTable
     * @param string $relationName
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    private function addMetaInformationField(Schema $schema, $ownerTable, $relationName, $isString = false)
    {
        if (!$schema->hasTable($ownerTable)) {
            return;
        }
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
                    'fallback_field' => $isString ? 'string' : 'text',
                ],
            ]
        );
    }
}
