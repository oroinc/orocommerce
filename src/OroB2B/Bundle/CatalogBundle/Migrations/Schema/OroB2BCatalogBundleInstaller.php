<?php

namespace OroB2B\Bundle\CatalogBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroB2BCatalogBundleInstaller implements
    Installation,
    NoteExtensionAwareInterface,
    AttachmentExtensionAwareInterface
{
    const ORO_B2B_CATALOG_CATEGORY_SHORT_DESCRIPTION_TABLE_NAME = 'orob2b_catalog_cat_short_desc';
    const ORO_B2B_CATALOG_CATEGORY_LONG_DESCRIPTION_TABLE_NAME = 'orob2b_catalog_cat_long_desc';
    const ORO_B2B_CATALOG_CATEGORY_TABLE_NAME = 'orob2b_catalog_category';
    const ORO_B2B_FALLBACK_LOCALIZE_TABLE_NAME ='oro_fallback_localization_val';
    const ORO_B2B_CATEGORY_UNIT_PRECISION_TABLE_NAME = 'orob2b_category_unit_precision';
    const ORO_B2B_PRODUCT_UNIT_TABLE_NAME = 'orob2b_product_unit';
    const MAX_CATEGORY_IMAGE_SIZE_IN_MB = 10;
    const THUMBNAIL_WIDTH_SIZE_IN_PX = 100;
    const THUMBNAIL_HEIGHT_SIZE_IN_PX = 100;

    /** @var NoteExtension */
    protected $noteExtension;

    /**
     * Sets the NoteExtension
     *
     * @param NoteExtension $noteExtension
     */
    public function setNoteExtension(NoteExtension $noteExtension)
    {
        $this->noteExtension = $noteExtension;
    }

    /** @var AttachmentExtension */
    protected $attachmentExtension;

    /**
     * {@inheritdoc}
     */
    public function setAttachmentExtension(AttachmentExtension $attachmentExtension)
    {
        $this->attachmentExtension = $attachmentExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_4';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BCatalogCategoryTable($schema);
        $this->createOroB2BCatalogCategoryTitleTable($schema);
        $this->createOrob2BCategoryToProductTable($schema);
        $this->createOroB2BCatalogCategoryShortDescriptionTable($schema);
        $this->createOroB2BCatalogCategoryLongDescriptionTable($schema);
        $this->createOroB2BCategoryUnitPrecisionTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BCatalogCategoryForeignKeys($schema);
        $this->addOroB2BCatalogCategoryTitleForeignKeys($schema);
        $this->addOrob2BCategoryToProductForeignKeys($schema);
        $this->addOroB2BCatalogCategoryShortDescriptionForeignKeys($schema);
        $this->addOroB2BCatalogCategoryLongDescriptionForeignKeys($schema);
        $this->addOroB2BCategoryUnitPrecisionForeignKeys($schema);
        $this->addCategoryImageAssociation($schema, 'largeImage');
        $this->addCategoryImageAssociation($schema, 'smallImage');
    }

    /**
     * Create orob2b_catalog_category table
     *
     * @param Schema $schema
     */
    protected function createOroB2BCatalogCategoryTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_catalog_category');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table->addColumn('tree_left', 'integer', []);
        $table->addColumn('tree_level', 'integer', []);
        $table->addColumn('tree_right', 'integer', []);
        $table->addColumn('tree_root', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('unit_precision_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['unit_precision_id']);
        $this->noteExtension->addNoteAssociation($schema, 'orob2b_catalog_category');
    }

    /**
     * Create orob2b_catalog_category_title table
     *
     * @param Schema $schema
     */
    protected function createOroB2BCatalogCategoryTitleTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_catalog_category_title');
        $table->addColumn('category_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['category_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Create orob2b_category_to_product table
     *
     * @param Schema $schema
     */
    protected function createOrob2BCategoryToProductTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_category_to_product');
        $table->addColumn('category_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->setPrimaryKey(['category_id', 'product_id']);
        $table->addUniqueIndex(['product_id']);
    }

    /**
     *
     * @param Schema $schema
     */
    protected function createOroB2BCategoryUnitPrecisionTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_B2B_CATEGORY_UNIT_PRECISION_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('unit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('unit_precision', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['unit_code'], 'IDX_D4D5D6E4FBD3D1C2');
    }

    /**
     * Add orob2b_catalog_category foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BCatalogCategoryForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_catalog_category');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_catalog_category'),
            ['parent_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_catalog_category_title foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BCatalogCategoryTitleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_catalog_category_title');
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_FALLBACK_LOCALIZE_TABLE_NAME),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_catalog_category'),
            ['category_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add orob2b_category_to_product foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BCategoryToProductForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_category_to_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
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

    /**
     *
     * @param Schema $schema
     */
    protected function addOroB2BCategoryUnitPrecisionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_B2B_CATALOG_CATEGORY_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_CATEGORY_UNIT_PRECISION_TABLE_NAME),
            ['unit_precision_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );

        $table = $schema->getTable(self::ORO_B2B_CATEGORY_UNIT_PRECISION_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_B2B_PRODUCT_UNIT_TABLE_NAME),
            ['unit_code'],
            ['code'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     * @param $fieldName
     */
    public function addCategoryImageAssociation(Schema $schema, $fieldName)
    {
        $this->attachmentExtension->addImageRelation(
            $schema,
            self::ORO_B2B_CATALOG_CATEGORY_TABLE_NAME,
            $fieldName,
            [],
            self::MAX_CATEGORY_IMAGE_SIZE_IN_MB,
            self::THUMBNAIL_WIDTH_SIZE_IN_PX,
            self::THUMBNAIL_HEIGHT_SIZE_IN_PX
        );
    }
}
