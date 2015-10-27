<?php

namespace OroB2B\Bundle\CatalogBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BCatalogBundle implements Migration
{
    const ORO_B2B_CATALOG_CATEGORY_SHORT_DESCRIPTION_TABLE_NAME = 'orob2b_catalog_cat_short_desc';
    const ORO_B2B_CATALOG_CATEGORY_LONG_DESCRIPTION_TABLE_NAME = 'orob2b_catalog_cat_long_desc';
    const ORO_B2B_CATALOG_CATEGORY_TABLE_NAME = 'orob2b_catalog_category';
    const ORO_B2B_FALLBACK_LOCALIZE_TABLE_NAME ='orob2b_fallback_locale_value';
    
    /**
     * @inheritDoc
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BCatalogCategoryShortDescriptionTable($schema);
        $this->createOroB2BCatalogCategoryLongDescriptionTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BCatalogCategoryShortDescriptionForeignKeys($schema);
        $this->addOroB2BCatalogCategoryLongDescriptionForeignKeys($schema);
    }

    /**
     * Create orob2b_catalog_category_short_description table
     *
     * @param Schema $schema
     */
    protected function createOroB2BCatalogCategoryShortDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_CATALOG_CATEGORY_SHORT_DESCRIPTION_TABLE_NAME);
        $table->addColumn('category_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->addUniqueIndex(['localized_value_id'], 'uniq_a2b14ef5eb576e89');
        $table->setPrimaryKey(['category_id', 'localized_value_id']);
    }

    /**
     * Create orob2b_catalog_category_long_description table
     *
     * @param Schema $schema
     */
    protected function createOroB2BCatalogCategoryLongDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_CATALOG_CATEGORY_LONG_DESCRIPTION_TABLE_NAME);
        $table->addColumn('category_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->addUniqueIndex(['localized_value_id'], 'uniq_4f7c279feb576e89');
        $table->setPrimaryKey(['category_id', 'localized_value_id']);
    }

    /**
     * Add orob2b_catalog_category_short_description foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BCatalogCategoryShortDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_CATALOG_CATEGORY_SHORT_DESCRIPTION_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_FALLBACK_LOCALIZE_TABLE_NAME),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_CATALOG_CATEGORY_TABLE_NAME),
            ['category_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_catalog_category_long_description foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BCatalogCategoryLongDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_CATALOG_CATEGORY_LONG_DESCRIPTION_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_FALLBACK_LOCALIZE_TABLE_NAME),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_CATALOG_CATEGORY_TABLE_NAME),
            ['category_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
