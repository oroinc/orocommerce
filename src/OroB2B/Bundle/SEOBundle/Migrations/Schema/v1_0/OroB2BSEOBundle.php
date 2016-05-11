<?php

namespace OroB2B\Bundle\SEOBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BSEOBundle implements Migration, ExtendExtensionAwareInterface
{
    const PRODUCT_TABLE_NAME = 'orob2b_product';
    const CATEGORY_TABLE_NAME = 'orob2b_catalog_category';
    const LANDING_PAGE_TABLE_NAME = 'orob2b_cms_page';
    const FALLBACK_LOCALE_VALUE_TABLE_NAME = 'orob2b_fallback_locale_value';
    
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

    private function addMetaInformation($schema, $ownerTable)
    {
        $this->addMetaInformationField($schema, $ownerTable, 'meta_titles');
        $this->addMetaInformationField($schema, $ownerTable, 'meta_descriptions');
        $this->addMetaInformationField($schema, $ownerTable, 'meta_keywords');
    }

    private function addMetaInformationField($schema, $ownerTable, $realationName)
    {
        $this->extendExtension->addManyToManyRelation(
            $schema,
            $ownerTable,
            $realationName,
            self::FALLBACK_LOCALE_VALUE_TABLE_NAME,
            ['id'],
            ['id'],
            ['id'],
            ['extend' => ['owner' => ExtendScope::OWNER_SYSTEM, 'without_default' => true]]
        );        
    }
}
