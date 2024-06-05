<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareTrait;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityBundle\Migration\AddFallbackRelationTrait;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration as LayoutThemeConfiguration;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtensionAwareInterface;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtensionAwareTrait;
use Oro\Bundle\ThemeBundle\Fallback\Provider\ThemeConfigurationFallbackProvider;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class OroProductBundleInstaller implements
    Installation,
    ExtendExtensionAwareInterface,
    ActivityExtensionAwareInterface,
    AttachmentExtensionAwareInterface,
    SlugExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;
    use ActivityExtensionAwareTrait;
    use AttachmentExtensionAwareTrait;
    use SlugExtensionAwareTrait;
    use AddFallbackRelationTrait;

    /**
     * {@inheritDoc}
     */
    public function getMigrationVersion(): string
    {
        return 'v1_34';
    }

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createOroProductTable($schema);
        $this->createOroProductUnitTable($schema);
        $this->createOroProductUnitPrecisionTable($schema);
        $this->createOroProductNameTable($schema);
        $this->createOroProductDescriptionTable($schema);
        $this->createOroProductVariantLinkTable($schema);
        $this->createOroProductShortDescriptionTable($schema);
        $this->createOroProductImageTable($schema);
        $this->createOroProductImageTypeTable($schema);
        $this->createOroProductSlugTable($schema);
        $this->createOroProductSlugPrototypeTable($schema);
        $this->createRelatedProductsTable($schema);
        $this->createUpsellProductTable($schema);

        $this->createOroBrandTable($schema);
        $this->createOroBrandDescriptionTable($schema);
        $this->createOroBrandNameTable($schema);
        $this->createOroBrandShortDescTable($schema);
        $this->createOroBrandSlugTable($schema);
        $this->createOroBrandSlugPrototypeTable($schema);

        $this->createOroProductKitItemTable($schema);
        $this->createOroProductKitItemLabelTable($schema);
        $this->createOroProductKitItemProductTable($schema);

        $this->createOroProductWebsiteReindexRequestItem($schema);

        $this->createCollectionSortOrderTable($schema);

        $this->addOroProductForeignKeys($schema);
        $this->addOroProductUnitPrecisionForeignKeys($schema);
        $this->addOroProductNameForeignKeys($schema);
        $this->addOroProductDescriptionForeignKeys($schema);
        $this->addOroProductVariantLinkForeignKeys($schema);
        $this->addOroProductShortDescriptionForeignKeys($schema);
        $this->addOroProductImageForeignKeys($schema);
        $this->addOroProductImageTypeForeignKeys($schema);
        $this->addBrandForeignKeys($schema);
        $this->addOroBrandDescriptionForeignKeys($schema);
        $this->addOroBrandNameForeignKeys($schema);
        $this->addOroBrandShortDescForeignKeys($schema);
        $this->addCollectionSortOrderForeignKeys($schema);
        $this->addOroProductKitItemForeignKeys($schema);
        $this->addOroProductKitItemLabelForeignKeys($schema);
        $this->addOroProductKitItemProductForeignKeys($schema);

        $this->addProductToBrand($schema);

        $this->updateProductTable($schema);
        $this->addNoteAssociations($schema);
        $this->addAttachmentAssociations($schema);
        $this->addProductContentVariants($schema);
        $this->addProductCollectionContentVariants($schema);
        $this->addAttributeFamilyField($schema);

        $this->addPageTemplateField($schema);
    }

    /**
     * Create oro_product table
     */
    private function createOroProductTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('sku', 'string', ['length' => 255]);
        $table->addColumn('sku_uppercase', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('name_uppercase', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->addColumn('variant_fields', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('status', 'string', ['length' => 16]);
        $table->addColumn('primary_unit_precision_id', 'integer', ['notnull' => false]);
        $table->addColumn('type', 'string', ['length' => 32]);
        $table->addColumn('is_featured', 'boolean', ['default' => false]);
        $table->addColumn('is_new_arrival', 'boolean', ['default' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['sku', 'organization_id'], 'uidx_oro_product_sku_organization');
        $table->addIndex(['created_at'], 'idx_oro_product_created_at');
        $table->addIndex(['updated_at'], 'idx_oro_product_updated_at');
        $table->addIndex(['sku'], 'idx_oro_product_sku');
        $table->addIndex(['sku_uppercase'], 'idx_oro_product_sku_uppercase');
        $table->addIndex(['name'], 'idx_oro_product_default_name');
        $table->addIndex(['name_uppercase'], 'idx_oro_product_default_name_uppercase');
        $table->addIndex(['created_at', 'id', 'organization_id'], 'idx_oro_product_created_at_id_organization');
        $table->addIndex(['updated_at', 'id', 'organization_id'], 'idx_oro_product_updated_at_id_organization');
        $table->addIndex(['sku', 'id', 'organization_id'], 'idx_oro_product_sku_id_organization');
        $table->addIndex(['status', 'id', 'organization_id'], 'idx_oro_product_status_id_organization');
        $table->addIndex(['is_featured'], 'idx_oro_product_featured', [], ['where' => '(is_featured = true)']);
        $table->addIndex(['id', 'updated_at'], 'idx_oro_product_id_updated_at');
        $table->addIndex(['is_new_arrival'], 'idx_oro_product_new_arrival', [], ['where' => '(is_new_arrival = true)']);
        $table->addIndex(['status'], 'idx_oro_product_status');
        $table->addUniqueIndex(['primary_unit_precision_id'], 'idx_oro_product_primary_unit_precision_id');
    }

    /**
     * Create oro_product_unit table
     */
    private function createOroProductUnitTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_product_unit');
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('default_precision', 'integer');
        $table->setPrimaryKey(['code']);
    }

    /**
     * Create oro_product_unit_precision table
     */
    private function createOroProductUnitPrecisionTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_product_unit_precision');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('unit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('unit_precision', 'integer');
        $table->addColumn('conversion_rate', 'float', ['notnull' => false]);
        $table->addColumn('sell', 'boolean', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['product_id', 'unit_code'], 'uidx_oro_product_unit_precision');
    }

    private function createOroProductNameTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_product_prod_name');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('string', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['fallback'], 'idx_product_prod_name_fallback');
        $table->addIndex(['string'], 'idx_product_prod_name_string');
    }

    private function createOroProductDescriptionTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_product_prod_descr');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('wysiwyg', 'wysiwyg', ['notnull' => false, 'comment' => '(DC2Type:wysiwyg)']);
        $table->addColumn('wysiwyg_style', 'wysiwyg_style', ['notnull' => false]);
        $table->addColumn('wysiwyg_properties', 'wysiwyg_properties', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['fallback'], 'idx_product_prod_descr_fallback');
    }

    /**
     * Create oro_product_slug table
     */
    private function createOroProductSlugTable(Schema $schema): void
    {
        $this->slugExtension->addSlugs(
            $schema,
            'oro_product_slug',
            'oro_product',
            'product_id'
        );
    }

    /**
     * Create oro_product_slug_prototype table
     */
    private function createOroProductSlugPrototypeTable(Schema $schema): void
    {
        $this->slugExtension->addLocalizedSlugPrototypes(
            $schema,
            'oro_product_slug_prototype',
            'oro_product',
            'product_id'
        );
    }

    /**
     * Add oro_product foreign keys.
     */
    private function addOroProductForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit_precision'),
            ['primary_unit_precision_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_product_unit_precision foreign keys.
     */
    private function addOroProductUnitPrecisionForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_product_unit_precision');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit'),
            ['unit_code'],
            ['code'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    private function addOroProductNameForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_product_prod_name');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
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

    private function addOroProductDescriptionForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_product_prod_descr');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
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

    private function updateProductTable(Schema $schema): void
    {
        $this->extendExtension->addEnumField(
            $schema,
            'oro_product',
            'inventory_status',
            'prod_inventory_status',
            false,
            false,
            [
                'importexport' => ['order' => '25'],
                'dataaudit' => ['auditable' => true],
                'frontend' => ['use_in_export' => true],
                'security' => ['permissions' => 'VIEW;EDIT']
            ],
        );
    }

    private function addNoteAssociations(Schema $schema): void
    {
        $this->activityExtension->addActivityAssociation($schema, 'oro_note', 'oro_product');
    }

    private function addAttachmentAssociations(Schema $schema): void
    {
        $this->attachmentExtension->addAttachmentAssociation($schema, 'oro_product', [], 5);
        $this->attachmentExtension->addImageRelation(
            $schema,
            'oro_product_image',
            'image',
            [
                'importexport' => [
                    'excluded' => false,
                ],
                'attachment' => [
                    'acl_protected' => false,
                    'use_dam' => true,
                ]
            ],
            10
        );
    }

    private function createOroProductVariantLinkTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_product_variant_link');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => true]);
        $table->addColumn('parent_product_id', 'integer', ['notnull' => true]);
        $table->addColumn('visible', 'boolean', ['default' => true]);
        $table->setPrimaryKey(['id']);
    }

    private function addOroProductVariantLinkForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_product_variant_link');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['parent_product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    private function createOroProductShortDescriptionTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_product_prod_s_descr');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('text', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['fallback'], 'idx_product_prod_s_descr_fallback');
    }

    private function addOroProductShortDescriptionForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_product_prod_s_descr');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
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

    private function createOroProductImageTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_product_image');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => true]);
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
    }

    private function createOroProductImageTypeTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_product_image_type');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_image_id', 'integer');
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['type'], 'idx_oro_product_image_type_type');
    }

    private function addOroProductImageForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_product_image');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    private function addOroProductImageTypeForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_product_image_type');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_image'),
            ['product_image_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    private function addProductContentVariants(Schema $schema): void
    {
        if ($schema->hasTable('oro_web_catalog_variant')) {
            $table = $schema->getTable('oro_web_catalog_variant');

            $this->extendExtension->addManyToOneRelation(
                $schema,
                $table,
                'product_page_product',
                'oro_product',
                'id',
                [
                    ExtendOptionsManager::MODE_OPTION => ConfigModel::MODE_READONLY,
                    'entity' => ['label' => 'oro.product.entity_label'],
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

    private function addProductCollectionContentVariants(Schema $schema): void
    {
        if ($schema->hasTable('oro_web_catalog_variant')) {
            $table = $schema->getTable('oro_web_catalog_variant');

            $this->extendExtension->addManyToOneRelation(
                $schema,
                $table,
                'product_collection_segment',
                'oro_segment',
                'id',
                [
                    'entity' => ['label' => 'oro.webcatalog.contentvariant.product_collection_segment.label'],
                    'extend' => [
                        'is_extend' => true,
                        'owner' => ExtendScope::OWNER_CUSTOM,
                        'cascade' => ['persist', 'remove'],
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

    private function addAttributeFamilyField(Schema $schema): void
    {
        $table = $schema->getTable('oro_product');
        $table->addColumn('attribute_family_id', 'integer', ['notnull' => false]);
        $table->addIndex(['attribute_family_id']);

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_attribute_family'),
            ['attribute_family_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'RESTRICT']
        );
    }

    private function addPageTemplateField(Schema $schema): void
    {
        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            'oro_product',
            'pageTemplate',
            'oro.product.page_template.label',
            [
                ThemeConfigurationFallbackProvider::FALLBACK_ID => [
                    'configName' => LayoutThemeConfiguration::buildOptionKey('product_details', 'template'),
                ],
                SystemConfigFallbackProvider::FALLBACK_ID => [
                    'configName' => 'oro_frontend.page_templates',
                ],
            ],
            ['security' => ['permissions' => 'VIEW;EDIT']]
        );
    }

    private function createRelatedProductsTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_product_related_products');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => true]);
        $table->addColumn('related_item_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['product_id'], 'idx_oro_product_related_products_product_id');
        $table->addIndex(['related_item_id'], 'idx_oro_product_related_products_related_item_id');
        $table->addUniqueIndex(['product_id', 'related_item_id'], 'idx_oro_product_related_products_unique');

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['related_item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    private function createUpsellProductTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_product_upsell_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => true]);
        $table->addColumn('related_item_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['product_id'], 'idx_oro_product_upsell_product_product_id');
        $table->addIndex(['related_item_id'], 'idx_oro_product_upsell_product_related_item_id');
        $table->addUniqueIndex(['product_id', 'related_item_id'], 'idx_oro_product_upsell_product_unique');

        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['related_item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create oro_brand table
     */
    private function createOroBrandTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_brand');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->addColumn('status', 'string', ['length' => 16]);
        $table->addColumn('default_title', 'string', ['length' => 255, 'notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['created_at'], 'idx_oro_brand_created_at');
        $table->addIndex(['updated_at'], 'idx_oro_brand_updated_at');
        $table->addIndex(['default_title'], 'idx_oro_brand_default_title');
    }

    /**
     * Create oro_brand_description table
     */
    private function createOroBrandDescriptionTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_brand_description');
        $table->addColumn('brand_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['brand_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_E42C1AB4EB576E89');
    }

    /**
     * Add oro_brand foreign keys.
     */
    private function addBrandForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_brand');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_business_unit'),
            ['business_unit_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Create oro_brand_name table
     */
    private function createOroBrandNameTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_brand_name');
        $table->addColumn('brand_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['brand_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_FA144D83EB576E89');
    }

    /**
     * Create oro_brand_short_desc table
     */
    private function createOroBrandShortDescTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_brand_short_desc');
        $table->addColumn('brand_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['brand_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_70031058EB576E89');
    }

    /**
     * Create oro_brand_slug table
     */
    private function createOroBrandSlugTable(Schema $schema): void
    {
        $this->slugExtension->addSlugs(
            $schema,
            'oro_brand_slug',
            'oro_brand',
            'brand_id'
        );
    }

    /**
     * Create oro_brand_slug_prototype table
     */
    private function createOroBrandSlugPrototypeTable(Schema $schema): void
    {
        $this->slugExtension->addLocalizedSlugPrototypes(
            $schema,
            'oro_brand_slug_prototype',
            'oro_brand',
            'brand_id'
        );
    }

    /**
     * Create oro_prod_webs_reindex_req_item table
     */
    private function createOroProductWebsiteReindexRequestItem(Schema $schema): void
    {
        $table = $schema->createTable('oro_prod_webs_reindex_req_item');
        $table->addColumn('related_job_id', 'integer', ['notnull' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => true]);
        $table->addIndex(['related_job_id'], 'related_job_id_idx');
        $table->addUniqueIndex(['product_id', 'related_job_id', 'website_id'], 'prod_webs_reindex_req_uniq_idx');
    }

    /**
     * Add oro_brand_description foreign keys.
     */
    private function addOroBrandDescriptionForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_brand_description');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_brand'),
            ['brand_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_brand_name foreign keys.
     */
    private function addOroBrandNameForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_brand_name');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_brand'),
            ['brand_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_brand_short_desc foreign keys.
     */
    private function addOroBrandShortDescForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_brand_short_desc');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_brand'),
            ['brand_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    private function addProductToBrand(Schema $schema): void
    {
        $table = $schema->getTable('oro_product');
        $table->addColumn('brand_id', 'integer', ['notnull' => false]);
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_brand'),
            ['brand_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Creates oro_product_collection_sort_order table
     */
    private function createCollectionSortOrderTable(Schema $schema): void
    {
        if (!$schema->hasTable('oro_product_collection_sort_order')) {
            $table = $schema->createTable('oro_product_collection_sort_order');
            $table->addColumn('id', 'integer', ['autoincrement' => true]);
            $table->addColumn('sort_order', 'float', [
                'notnull' => false,
                'default' => null
            ]);
            $table->addColumn('product_id', 'integer', ['notnull' => true]);
            $table->addColumn('segment_id', 'integer', ['notnull' => true]);
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(['product_id', 'segment_id'], 'product_segment_sort_uniq_idx');
        }
    }

    /**
     * Add foreign keys to the oro_product_collection_sort_order table
     */
    private function addCollectionSortOrderForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_product_collection_sort_order');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_segment'),
            ['segment_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    private function createOroProductKitItemTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_product_kit_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->addColumn('optional', 'boolean', ['default' => false]);
        $table->addColumn('sort_order', 'integer', ['default' => 0]);
        $table->addColumn('minimum_quantity', 'float', ['notnull' => false]);
        $table->addColumn('maximum_quantity', 'float', ['notnull' => false]);
        $table->addColumn('unit_code', 'string', ['notnull' => false]);
        $table->addColumn('product_kit_id', 'integer', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
    }

    private function addOroProductKitItemForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_product_kit_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_kit_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit'),
            ['unit_code'],
            ['code'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    private function createOroProductKitItemLabelTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_product_prod_kit_item_label');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_kit_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('string', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['fallback'], 'idx_product_prod_kit_fallback');
        $table->addIndex(['string'], 'idx_product_prod_kit_string');
    }

    private function addOroProductKitItemLabelForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_product_prod_kit_item_label');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_kit_item'),
            ['product_kit_item_id'],
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

    private function createOroProductKitItemProductTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_product_kit_item_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_kit_item_id', 'integer');
        $table->addColumn('product_id', 'integer');
        $table->addColumn('product_unit_precision_id', 'integer', ['notnull' => false]);
        $table->addColumn('sort_order', 'integer', ['default' => 0]);
        $table->setPrimaryKey(['id']);
    }

    private function addOroProductKitItemProductForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_product_kit_item_product');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_kit_item'),
            ['product_kit_item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit_precision'),
            ['product_unit_precision_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
