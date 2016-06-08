<?php

namespace OroB2B\Bundle\SEOBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BSEOBundle implements Migration, ExtendExtensionAwareInterface
{
    const PRODUCT_TABLE_NAME = 'orob2b_product';
    const CATEGORY_TABLE_NAME = 'orob2b_catalog_category';
    const LANDING_PAGE_TABLE_NAME = 'orob2b_cms_page';
    const FALLBACK_LOCALE_VALUE_TABLE_NAME = 'oro_fallback_localization_val';
    
    /** @var ExtendExtension */
    protected $extendExtension;

    /**
     * Sets the ExtendExtension
     *
     * @param ExtendExtension $extendExtension
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * Modifies the given schema to apply necessary changes of a database
     * The given query bag can be used to apply additional SQL queries before and after schema changes
     *
     * @param Schema $schema
     * @param QueryBag $queries
     * @return void
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addMetaInformation($schema, self::PRODUCT_TABLE_NAME);
        $this->addMetaInformation($schema, self::CATEGORY_TABLE_NAME);
        $this->addMetaInformation($schema, self::LANDING_PAGE_TABLE_NAME);
    }

    /**
     * Method that adds 3 meta fields (metaTitles, metaDescription, metaKeywords) relations to the
     * received table (corresponding to a an entitiy).
     *
     * @param Schema $schema
     * @param string $ownerTable
     */
    private function addMetaInformation($schema, $ownerTable)
    {
        $this->addMetaInformationField($schema, $ownerTable, 'metaTitles');
        $this->addMetaInformationField($schema, $ownerTable, 'metaDescriptions');
        $this->addMetaInformationField($schema, $ownerTable, 'metaKeywords');
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
    private function addMetaInformationField($schema, $ownerTable, $relationName)
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
            [
                'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                'form' => ['is_enabled' => false],
                'view' => ['is_displayable' => false],
            ]
        );
    }
}
