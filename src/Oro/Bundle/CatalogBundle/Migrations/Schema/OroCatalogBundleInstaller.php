<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\CommerceMenuBundle\Migrations\Schema\OroCommerceMenuBundleInstaller;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\ProductBundle\Migrations\Schema\OroProductBundleInstaller;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtension;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtensionAwareInterface;

/**
 * Handles all migrations logic executed during installation.
 *
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroCatalogBundleInstaller implements
    Installation,
    ActivityExtensionAwareInterface,
    AttachmentExtensionAwareInterface,
    ExtendExtensionAwareInterface,
    SlugExtensionAwareInterface
{
    use AttachmentExtensionAwareTrait;

    const ORO_CATALOG_CATEGORY_TABLE_NAME = 'oro_catalog_category';
    const ORO_CATEGORY_DEFAULT_PRODUCT_OPTIONS_TABLE_NAME = 'oro_category_def_prod_opts';
    const ORO_PRODUCT_TABLE_NAME = 'oro_product';
    const ORO_PRODUCT_UNIT_TABLE_NAME = 'oro_product_unit';
    const MAX_CATEGORY_IMAGE_SIZE_IN_MB = 10;
    const THUMBNAIL_WIDTH_SIZE_IN_PX = 100;
    const THUMBNAIL_HEIGHT_SIZE_IN_PX = 100;
    const MIME_TYPES = [
        'image/gif',
        'image/jpeg',
        'image/png',
        'image/svg+xml'
    ];

    /** @var ActivityExtension */
    protected $activityExtension;

    /**
     * @var ExtendExtension
     */
    protected $extendExtension;

    /**
     * @var SlugExtension
     */
    protected $slugExtension;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_21';
    }

    /**
     * {@inheritdoc}
     */
    public function setActivityExtension(ActivityExtension $activityExtension)
    {
        $this->activityExtension = $activityExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setSlugExtension(SlugExtension $extension)
    {
        $this->slugExtension = $extension;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroCatalogCategoryTable($schema);
        $this->createOroCatalogCategoryTitleTable($schema);
        $this->createOroCatalogCategoryShortDescriptionTable($schema);
        $this->createOroCatalogCategoryLongDescriptionTable($schema);
        $this->createOroCategoryDefaultProductOptionsTable($schema);
        $this->createOroCategorySlugTable($schema);
        $this->createOroCategorySlugPrototypeTable($schema);

        /** Foreign keys generation **/
        $this->addOroCatalogCategoryForeignKeys($schema);
        $this->addOroCatalogCategoryTitleForeignKeys($schema);
        $this->addOroCatalogCategoryShortDescriptionForeignKeys($schema);
        $this->addOroCatalogCategoryLongDescriptionForeignKeys($schema);
        $this->addOroCategoryDefaultProductOptionsForeignKeys($schema);
        $this->addCategoryImageAssociation($schema, 'largeImage', self::MIME_TYPES);
        $this->addCategoryImageAssociation($schema, 'smallImage', self::MIME_TYPES);

        $this->addCategoryProductRelation($schema);
        $this->addCategoryMenuUpdateRelation($schema);

        $this->addContentVariantTypes($schema);

        $this->createProductCategorySortOrder($schema);
    }

    /**
     * Create oro_catalog_category table
     */
    protected function createOroCatalogCategoryTable(Schema $schema)
    {
        $importExportOptions = [
            OroOptions::KEY => [
                'importexport' => ['excluded' => true],
            ],
        ];
        $table = $schema->createTable('oro_catalog_category');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table->addColumn('tree_left', 'integer', $importExportOptions);
        $table->addColumn('tree_level', 'integer', $importExportOptions);
        $table->addColumn('tree_right', 'integer', $importExportOptions);
        $table->addColumn('tree_root', 'integer', ['notnull' => false] + $importExportOptions);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)'] + $importExportOptions);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)'] + $importExportOptions);
        $table->addColumn('default_product_options_id', 'integer', ['notnull' => false]);
        $table->addColumn('materialized_path', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('title', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['default_product_options_id']);
        $table->addIndex(['title'], 'idx_oro_category_default_title', []);
        $this->activityExtension->addActivityAssociation($schema, 'oro_note', self::ORO_CATALOG_CATEGORY_TABLE_NAME);
    }

    /**
     * Create oro_catalog_cat_title table
     */
    protected function createOroCatalogCategoryTitleTable(Schema $schema)
    {
        $table = $schema->createTable('oro_catalog_cat_title');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('string', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['fallback'], 'idx_cat_cat_title_fallback', []);
        $table->addIndex(['string'], 'idx_cat_cat_title_string', []);
    }

    protected function createOroCategoryDefaultProductOptionsTable(Schema $schema)
    {
        $table = $schema->createTable(self::ORO_CATEGORY_DEFAULT_PRODUCT_OPTIONS_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_unit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('product_unit_precision', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_catalog_cat_slug table
     */
    protected function createOroCategorySlugTable(Schema $schema)
    {
        $this->slugExtension->addSlugs(
            $schema,
            'oro_catalog_cat_slug',
            'oro_catalog_category',
            'category_id'
        );
    }

    /**
     * Create oro_catalog_cat_slug_prototype table
     */
    protected function createOroCategorySlugPrototypeTable(Schema $schema)
    {
        $this->slugExtension->addLocalizedSlugPrototypes(
            $schema,
            'oro_catalog_cat_slug_prototype',
            'oro_catalog_category',
            'category_id'
        );
    }

    /**
     * Add oro_catalog_category foreign keys.
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

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add oro_catalog_cat_title foreign keys.
     */
    protected function addOroCatalogCategoryTitleForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_catalog_cat_title');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['category_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_localization'),
            ['localization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Create oro_catalog_cat_s_descr table
     */
    protected function createOroCatalogCategoryShortDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable('oro_catalog_cat_s_descr');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('text', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['fallback'], 'idx_cat_cat_s_descr_fallback', []);
    }

    /**
     * Create oro_catalog_cat_l_descr table
     */
    protected function createOroCatalogCategoryLongDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable('oro_catalog_cat_l_descr');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('wysiwyg', 'wysiwyg', ['notnull' => false, 'comment' => '(DC2Type:wysiwyg)']);
        $table->addColumn('wysiwyg_style', 'wysiwyg_style', ['notnull' => false]);
        $table->addColumn('wysiwyg_properties', 'wysiwyg_properties', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['fallback'], 'idx_cat_cat_l_descr_fallback', []);
    }

    /**
     * Add oro_catalog_cat_s_descr foreign keys.
     */
    protected function addOroCatalogCategoryShortDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_catalog_cat_s_descr');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['category_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_localization'),
            ['localization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_catalog_cat_l_descr foreign keys.
     */
    protected function addOroCatalogCategoryLongDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_catalog_cat_l_descr');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['category_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_localization'),
            ['localization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

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
     * @param string $fieldName
     * @param array  $mimeTypes
     */
    public function addCategoryImageAssociation(Schema $schema, $fieldName, array $mimeTypes = [])
    {
        $this->attachmentExtension->addImageRelation(
            $schema,
            self::ORO_CATALOG_CATEGORY_TABLE_NAME,
            $fieldName,
            [
                'attachment' => [
                    'acl_protected' => false,
                ],
                'importexport' => ['excluded' => true],
            ],
            self::MAX_CATEGORY_IMAGE_SIZE_IN_MB,
            self::THUMBNAIL_WIDTH_SIZE_IN_PX,
            self::THUMBNAIL_HEIGHT_SIZE_IN_PX,
            $mimeTypes
        );
    }

    public function addContentVariantTypes(Schema $schema)
    {
        if ($schema->hasTable('oro_web_catalog_variant')) {
            $table = $schema->getTable('oro_web_catalog_variant');
            $table->addColumn(
                'exclude_subcategories',
                'boolean',
                [
                    OroOptions::KEY => [
                        ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                        'entity' => ['label' => 'oro.catalog.category.include_subcategories.label'],
                        'extend' => [
                            'is_extend' => true,
                            'owner' => ExtendScope::OWNER_CUSTOM,
                        ],
                        'datagrid' => [
                            'is_visible' => DatagridScope::IS_VISIBLE_FALSE
                        ],
                        'form' => [
                            'is_enabled' => false,
                        ],
                        'view' => ['is_displayable' => false],
                        'merge' => ['display' => false],
                        'dataaudit' => ['auditable' => true],
                    ],
                ]
            );

            $this->extendExtension->addManyToOneRelation(
                $schema,
                $table,
                'category_page_category',
                'oro_catalog_category',
                'id',
                [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                    'entity' => ['label' => 'oro.catalog.category.entity_label'],
                    'extend' => [
                        'is_extend' => true,
                        'owner' => ExtendScope::OWNER_CUSTOM,
                        'cascade' => ['persist'],
                        'on_delete' => 'CASCADE',
                    ],
                    'datagrid' => [
                        'is_visible' => DatagridScope::IS_VISIBLE_FALSE
                    ],
                    'form' => [
                        'is_enabled' => false
                    ],
                    'view' => ['is_displayable' => false],
                    'merge' => ['display' => false],
                    'dataaudit' => ['auditable' => true]
                ]
            );
        }
    }

    protected function addCategoryProductRelation(Schema $schema)
    {
        $table = $schema->getTable(OroCatalogBundleInstaller::ORO_CATALOG_CATEGORY_TABLE_NAME);
        $targetTable = $schema->getTable(OroProductBundleInstaller::PRODUCT_TABLE_NAME);

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $targetTable,
            'category',
            $table,
            'id',
            [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'without_default' => true,
                    'cascade' => ['persist'],
                    'on_delete' => 'SET NULL',
                ],
                'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                'form' => ['is_enabled' => false],
                'view' => ['is_displayable' => false],
                'merge' => ['display' => false]
            ]
        );

        $this->extendExtension->addManyToOneInverseRelation(
            $schema,
            $targetTable,
            'category',
            $table,
            'products',
            ['name'],
            ['name'],
            ['name'],
            [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'without_default' => true,
                    'fetch' => 'extra_lazy',
                    'on_delete' => 'SET NULL',
                ],
                'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                'form' => ['is_enabled' => false],
                'view' => ['is_displayable' => false],
                'merge' => ['display' => false],
                'importexport' => ['excluded' => true],
            ]
        );
    }

    /**
     * Adds category_sort_order field to oro_product table & related extended field
     * @param Schema $schema
     * @return void
     */
    protected function createProductCategorySortOrder(Schema $schema): void
    {
        $table = $schema->getTable(OroCatalogBundleInstaller::ORO_PRODUCT_TABLE_NAME);
        if (!$table->hasColumn('category_sort_order')) {
            $table->addColumn('category_sort_order', 'float', [
                'notnull' => false,
                'default' => null,
                'oro_options' => [
                    'extend' => ['owner' => ExtendScope::OWNER_CUSTOM],
                    'importexport' => ['excluded' => true],
                    'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                    'form' => ['is_enabled' => false],
                    'email' => ['available_in_template' => false],
                    'view' => ['is_displayable' => false],
                    'merge' => ['display' => false],
                ],
            ]);
        }
    }

    protected function addCategoryMenuUpdateRelation(Schema $schema): void
    {
        $table = $schema->getTable(OroCatalogBundleInstaller::ORO_CATALOG_CATEGORY_TABLE_NAME);
        $targetTable = $schema->getTable(OroCommerceMenuBundleInstaller::ORO_COMMERCE_MENU_UPDATE_TABLE_NAME);

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $targetTable,
            'category',
            $table,
            'id',
            [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'on_delete' => 'CASCADE',
                ],
                'form' => ['is_enabled' => false],
            ]
        );
    }
}
