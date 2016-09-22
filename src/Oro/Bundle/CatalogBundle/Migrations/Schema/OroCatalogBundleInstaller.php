<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema;

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
class OroCatalogBundleInstaller implements
    Installation,
    NoteExtensionAwareInterface,
    AttachmentExtensionAwareInterface
{
    const ORO_CATALOG_CATEGORY_SHORT_DESCRIPTION_TABLE_NAME = 'oro_catalog_cat_short_desc';
    const ORO_CATALOG_CATEGORY_LONG_DESCRIPTION_TABLE_NAME = 'oro_catalog_cat_long_desc';
    const ORO_CATALOG_CATEGORY_TABLE_NAME = 'oro_catalog_category';
    const ORO_FALLBACK_LOCALIZE_TABLE_NAME ='oro_fallback_localization_val';
    const ORO_CATEGORY_DEFAULT_PRODUCT_OPTIONS_TABLE_NAME = 'oro_category_def_prod_opts';
    const ORO_PRODUCT_UNIT_TABLE_NAME = 'oro_product_unit';
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
        $this->createOroCatalogCategoryTable($schema);
        $this->createOroCatalogCategoryTitleTable($schema);
        $this->createOroCategoryToProductTable($schema);
        $this->createOroCatalogCategoryShortDescriptionTable($schema);
        $this->createOroCatalogCategoryLongDescriptionTable($schema);
        $this->createOroCategoryDefaultProductOptionsTable($schema);

        /** Foreign keys generation **/
        $this->addOroCatalogCategoryForeignKeys($schema);
        $this->addOroCatalogCategoryTitleForeignKeys($schema);
        $this->addOroCategoryToProductForeignKeys($schema);
        $this->addOroCatalogCategoryShortDescriptionForeignKeys($schema);
        $this->addOroCatalogCategoryLongDescriptionForeignKeys($schema);
        $this->addOroCategoryDefaultProductOptionsForeignKeys($schema);
        $this->addCategoryImageAssociation($schema, 'largeImage');
        $this->addCategoryImageAssociation($schema, 'smallImage');
    }

    /**
     * Create oro_catalog_category table
     *
     * @param Schema $schema
     */
    protected function createOroCatalogCategoryTable(Schema $schema)
    {
        $table = $schema->createTable('oro_catalog_category');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table->addColumn('tree_left', 'integer', []);
        $table->addColumn('tree_level', 'integer', []);
        $table->addColumn('tree_right', 'integer', []);
        $table->addColumn('tree_root', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('default_product_options_id', 'integer', ['notnull' => false]);
        $table->addColumn('materialized_path', 'string', ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['default_product_options_id']);
        $this->noteExtension->addNoteAssociation($schema, 'oro_catalog_category');
    }

    /**
     * Create oro_catalog_category_title table
     *
     * @param Schema $schema
     */
    protected function createOroCatalogCategoryTitleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_catalog_category_title');
        $table->addColumn('category_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['category_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * Create oro_category_to_product table
     *
     * @param Schema $schema
     */
    protected function createOroCategoryToProductTable(Schema $schema)
    {
        $table = $schema->createTable('oro_category_to_product');
        $table->addColumn('category_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->setPrimaryKey(['category_id', 'product_id']);
        $table->addUniqueIndex(['product_id']);
    }

    /**
     * @param Schema $schema
     */
    protected function createOroCategoryDefaultProductOptionsTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_CATEGORY_DEFAULT_PRODUCT_OPTIONS_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_unit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('product_unit_precision', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_catalog_category foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCatalogCategoryForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_catalog_category');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['parent_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_CATEGORY_DEFAULT_PRODUCT_OPTIONS_TABLE_NAME),
            ['default_product_options_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_catalog_category_title foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCatalogCategoryTitleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_catalog_category_title');
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_FALLBACK_LOCALIZE_TABLE_NAME),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['category_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_category_to_product foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCategoryToProductForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_category_to_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create oro_catalog_category_short_description table
     *
     * @param Schema $schema
     */
    protected function createOroCatalogCategoryShortDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_CATALOG_CATEGORY_SHORT_DESCRIPTION_TABLE_NAME);
        $table->addColumn('category_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->addUniqueIndex(['localized_value_id'], 'uniq_a2b14ef5eb576e89');
        $table->setPrimaryKey(['category_id', 'localized_value_id']);
    }

    /**
     * Create oro_catalog_category_long_description table
     *
     * @param Schema $schema
     */
    protected function createOroCatalogCategoryLongDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_CATALOG_CATEGORY_LONG_DESCRIPTION_TABLE_NAME);
        $table->addColumn('category_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->addUniqueIndex(['localized_value_id'], 'uniq_4f7c279feb576e89');
        $table->setPrimaryKey(['category_id', 'localized_value_id']);
    }

    /**
     * Add oro_catalog_category_short_description foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCatalogCategoryShortDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_CATALOG_CATEGORY_SHORT_DESCRIPTION_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_FALLBACK_LOCALIZE_TABLE_NAME),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_CATALOG_CATEGORY_TABLE_NAME),
            ['category_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
    
    /**
     * Add oro_catalog_category_long_description foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroCatalogCategoryLongDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_CATALOG_CATEGORY_LONG_DESCRIPTION_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_FALLBACK_LOCALIZE_TABLE_NAME),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_CATALOG_CATEGORY_TABLE_NAME),
            ['category_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addOroCategoryDefaultProductOptionsForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::ORO_CATEGORY_DEFAULT_PRODUCT_OPTIONS_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::ORO_PRODUCT_UNIT_TABLE_NAME),
            ['product_unit_code'],
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
            self::ORO_CATALOG_CATEGORY_TABLE_NAME,
            $fieldName,
            [],
            self::MAX_CATEGORY_IMAGE_SIZE_IN_MB,
            self::THUMBNAIL_WIDTH_SIZE_IN_PX,
            self::THUMBNAIL_HEIGHT_SIZE_IN_PX
        );
    }
}
