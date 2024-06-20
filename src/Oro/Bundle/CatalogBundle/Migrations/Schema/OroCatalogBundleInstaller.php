<?php

namespace Oro\Bundle\CatalogBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareTrait;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\OroOptions;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtensionAwareInterface;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtensionAwareTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroCatalogBundleInstaller implements
    Installation,
    ActivityExtensionAwareInterface,
    AttachmentExtensionAwareInterface,
    ExtendExtensionAwareInterface,
    SlugExtensionAwareInterface
{
    use ActivityExtensionAwareTrait;
    use AttachmentExtensionAwareTrait;
    use ExtendExtensionAwareTrait;
    use SlugExtensionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_22';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
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

        $this->addCategoryImageAssociation($schema, 'largeImage');
        $this->addCategoryImageAssociation($schema, 'smallImage');

        $this->addCategoryProductRelation($schema);
        $this->addCategoryMenuUpdateRelation($schema);

        $this->addContentVariantTypes($schema);

        $this->createProductCategorySortOrder($schema);
        $this->addCategoryToSearchTermTable($schema);
    }

    /**
     * Create oro_catalog_category table
     */
    private function createOroCatalogCategoryTable(Schema $schema): void
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
        $table->addIndex(['title'], 'idx_oro_category_default_title');
        $this->activityExtension->addActivityAssociation($schema, 'oro_note', 'oro_catalog_category');
    }

    /**
     * Create oro_catalog_cat_title table
     */
    private function createOroCatalogCategoryTitleTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_catalog_cat_title');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('string', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['fallback'], 'idx_cat_cat_title_fallback');
        $table->addIndex(['string'], 'idx_cat_cat_title_string');
    }

    private function createOroCategoryDefaultProductOptionsTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_category_def_prod_opts');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_unit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('product_unit_precision', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_catalog_cat_slug table
     */
    private function createOroCategorySlugTable(Schema $schema): void
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
    private function createOroCategorySlugPrototypeTable(Schema $schema): void
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
    private function addOroCatalogCategoryForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_catalog_category');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_catalog_category'),
            ['parent_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_category_def_prod_opts'),
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
    private function addOroCatalogCategoryTitleForeignKeys(Schema $schema): void
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
    private function createOroCatalogCategoryShortDescriptionTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_catalog_cat_s_descr');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('text', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['fallback'], 'idx_cat_cat_s_descr_fallback');
    }

    /**
     * Create oro_catalog_cat_l_descr table
     */
    private function createOroCatalogCategoryLongDescriptionTable(Schema $schema): void
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
        $table->addIndex(['fallback'], 'idx_cat_cat_l_descr_fallback');
    }

    /**
     * Add oro_catalog_cat_s_descr foreign keys.
     */
    private function addOroCatalogCategoryShortDescriptionForeignKeys(Schema $schema): void
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
    private function addOroCatalogCategoryLongDescriptionForeignKeys(Schema $schema): void
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

    private function addOroCategoryDefaultProductOptionsForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_category_def_prod_opts');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit'),
            ['product_unit_code'],
            ['code'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    private function addCategoryImageAssociation(Schema $schema, string $fieldName): void
    {
        $this->attachmentExtension->addImageRelation(
            $schema,
            'oro_catalog_category',
            $fieldName,
            [
                'attachment' => [
                    'acl_protected' => false,
                ],
                'importexport' => ['excluded' => true],
            ],
            10,
            100,
            100,
            ['image/gif', 'image/jpeg', 'image/png', 'image/svg+xml']
        );
    }

    private function addContentVariantTypes(Schema $schema): void
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

    private function addCategoryProductRelation(Schema $schema): void
    {
        $table = $schema->getTable('oro_catalog_category');
        $targetTable = $schema->getTable('oro_product');

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
     */
    private function createProductCategorySortOrder(Schema $schema): void
    {
        $table = $schema->getTable('oro_product');
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

    private function addCategoryMenuUpdateRelation(Schema $schema): void
    {
        $table = $schema->getTable('oro_catalog_category');
        $targetTable = $schema->getTable('oro_commerce_menu_upd');

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

    private function addCategoryToSearchTermTable(Schema $schema)
    {
        $owningSideTable = $schema->getTable('oro_website_search_search_term');
        $inverseSideTable = $schema->getTable('oro_catalog_category');

        $this->extendExtension->addManyToOneRelation(
            $schema,
            $owningSideTable,
            'redirectCategory',
            $inverseSideTable,
            'id',
            [
                ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                'extend' => [
                    'is_extend' => true,
                    'owner' => ExtendScope::OWNER_CUSTOM,
                    'without_default' => true,
                    'on_delete' => 'SET NULL',
                ],
                'datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_FALSE],
                'view' => ['is_displayable' => false],
                'form' => ['is_enabled' => false],
            ]
        );
    }
}
