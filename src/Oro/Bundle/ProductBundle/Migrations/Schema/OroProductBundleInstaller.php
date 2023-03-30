<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityBundle\Migration\AddFallbackRelationTrait;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\ExtendOptionsManager;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtension;
use Oro\Bundle\RedirectBundle\Migration\Extension\SlugExtensionAwareInterface;

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
    use AttachmentExtensionAwareTrait;
    use AddFallbackRelationTrait;

    const PRODUCT_TABLE_NAME = 'oro_product';
    const BRAND_TABLE_NAME = 'oro_brand';
    const PRODUCT_UNIT_TABLE_NAME = 'oro_product_unit';
    const PRODUCT_UNIT_PRECISION_TABLE_NAME = 'oro_product_unit_precision';
    const PRODUCT_VARIANT_LINK_TABLE_NAME = 'oro_product_variant_link';
    const FALLBACK_LOCALE_VALUE_TABLE_NAME = 'oro_fallback_localization_val';
    const RELATED_PRODUCTS_TABLE_NAME = 'oro_product_related_products';
    const UPSELL_PRODUCTS_TABLE_NAME = 'oro_product_upsell_product';

    const MAX_PRODUCT_IMAGE_SIZE_IN_MB = 10;
    const MAX_PRODUCT_ATTACHMENT_SIZE_IN_MB = 5;

    const PRODUCT_IMAGE_TABLE_NAME = 'oro_product_image';
    const PRODUCT_IMAGE_TYPE_TABLE_NAME = 'oro_product_image_type';

    public const PRODUCT_COLLECTION_SORT_ORDER_TABLE_NAME = 'oro_product_collection_sort_order';

    public const PRODUCT_WEBSITE_REINDEX_REQUEST_ITEM = 'oro_prod_webs_reindex_req_item';

    /** @var ExtendExtension */
    protected $extendExtension;

    /** @var  ActivityExtension */
    protected $activityExtension;

    /**
     * @var SlugExtension
     */
    protected $slugExtension;

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
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
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
    public function getMigrationVersion()
    {
        return 'v1_29';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
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
    protected function createOroProductTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('sku', 'string', ['length' => 255]);
        $table->addColumn('sku_uppercase', 'string', ['length' => 255, 'notnull' => false]);
        $table->addColumn('name', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('name_uppercase', 'string', ['length' => 255, 'notnull' => true]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('variant_fields', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('status', 'string', ['length' => 16]);
        $table->addColumn('primary_unit_precision_id', 'integer', ['notnull' => false]);
        $table->addColumn('type', 'string', ['length' => 32]);
        $table->addColumn('is_featured', 'boolean', ['default' => false]);
        $table->addColumn('is_new_arrival', 'boolean', ['default' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['sku', 'organization_id'], 'uidx_oro_product_sku_organization');
        $table->addIndex(['created_at'], 'idx_oro_product_created_at', []);
        $table->addIndex(['updated_at'], 'idx_oro_product_updated_at', []);
        $table->addIndex(['sku'], 'idx_oro_product_sku', []);
        $table->addIndex(['sku_uppercase'], 'idx_oro_product_sku_uppercase', []);
        $table->addIndex(['name'], 'idx_oro_product_default_name', []);
        $table->addIndex(['name_uppercase'], 'idx_oro_product_default_name_uppercase', []);
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
    protected function createOroProductUnitTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_UNIT_TABLE_NAME);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('default_precision', 'integer');
        $table->setPrimaryKey(['code']);
    }

    /**
     * Create oro_product_unit_precision table
     */
    protected function createOroProductUnitPrecisionTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_UNIT_PRECISION_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('unit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('unit_precision', 'integer', []);
        $table->addColumn('conversion_rate', 'float', ['notnull' => false]);
        $table->addColumn('sell', 'boolean', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['product_id', 'unit_code'], 'uidx_oro_product_unit_precision');
    }

    protected function createOroProductNameTable(Schema $schema)
    {
        $table = $schema->createTable('oro_product_prod_name');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('string', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['fallback'], 'idx_product_prod_name_fallback', []);
        $table->addIndex(['string'], 'idx_product_prod_name_string', []);
    }

    protected function createOroProductDescriptionTable(Schema $schema)
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
        $table->addIndex(['fallback'], 'idx_product_prod_descr_fallback', []);
    }

    /**
     * Create oro_product_slug table
     */
    protected function createOroProductSlugTable(Schema $schema)
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
    protected function createOroProductSlugPrototypeTable(Schema $schema)
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
    protected function addOroProductForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_TABLE_NAME);
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
            $schema->getTable(self::PRODUCT_UNIT_PRECISION_TABLE_NAME),
            ['primary_unit_precision_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_product_unit_precision foreign keys.
     */
    protected function addOroProductUnitPrecisionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_UNIT_PRECISION_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_TABLE_NAME),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_UNIT_TABLE_NAME),
            ['unit_code'],
            ['code'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    protected function addOroProductNameForeignKeys(Schema $schema)
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

    protected function addOroProductDescriptionForeignKeys(Schema $schema)
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

    protected function updateProductTable(Schema $schema)
    {
        $this->extendExtension->addEnumField(
            $schema,
            self::PRODUCT_TABLE_NAME,
            'inventory_status',
            'prod_inventory_status',
            false,
            false,
            [
                'importexport' => ['order' => '25'],
                'dataaudit' => ['auditable' => true],
                'frontend' => ['use_in_export' => true],
            ]
        );
    }

    protected function addNoteAssociations(Schema $schema)
    {
        $this->activityExtension->addActivityAssociation($schema, 'oro_note', self::PRODUCT_TABLE_NAME);
    }

    protected function addAttachmentAssociations(Schema $schema)
    {
        $this->attachmentExtension->addAttachmentAssociation(
            $schema,
            self::PRODUCT_TABLE_NAME,
            [],
            self::MAX_PRODUCT_ATTACHMENT_SIZE_IN_MB
        );

        $this->attachmentExtension->addImageRelation(
            $schema,
            self::PRODUCT_IMAGE_TABLE_NAME,
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
            self::MAX_PRODUCT_IMAGE_SIZE_IN_MB
        );
    }

    protected function createOroProductVariantLinkTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_VARIANT_LINK_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => true]);
        $table->addColumn('parent_product_id', 'integer', ['notnull' => true]);
        $table->addColumn('visible', 'boolean', ['default' => true]);
        $table->setPrimaryKey(['id']);
    }

    protected function addOroProductVariantLinkForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_VARIANT_LINK_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_TABLE_NAME),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_TABLE_NAME),
            ['parent_product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    protected function createOroProductShortDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable('oro_product_prod_s_descr');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('text', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['fallback'], 'idx_product_prod_s_descr_fallback', []);
    }

    protected function addOroProductShortDescriptionForeignKeys(Schema $schema)
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

    protected function createOroProductImageTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_IMAGE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => true]);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
    }

    protected function createOroProductImageTypeTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_IMAGE_TYPE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_image_id', 'integer');
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->addIndex(['type'], 'idx_oro_product_image_type_type');
        $table->setPrimaryKey(['id']);
    }

    protected function addOroProductImageForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_IMAGE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_TABLE_NAME),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    protected function addOroProductImageTypeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_IMAGE_TYPE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_IMAGE_TABLE_NAME),
            ['product_image_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    public function addProductContentVariants(Schema $schema)
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

    private function addProductCollectionContentVariants(Schema $schema)
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

    public function addAttributeFamilyField(Schema $schema)
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

    public function addPageTemplateField(Schema $schema)
    {
        $this->addFallbackRelation(
            $schema,
            $this->extendExtension,
            self::PRODUCT_TABLE_NAME,
            'pageTemplate',
            'oro.product.page_template.label',
            [
                SystemConfigFallbackProvider::FALLBACK_ID => [
                    'configName' => 'oro_frontend.page_templates',
                ],
            ]
        );
    }

    private function createRelatedProductsTable(Schema $schema)
    {
        $table = $schema->createTable(self::RELATED_PRODUCTS_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => true]);
        $table->addColumn('related_item_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['product_id'], 'idx_oro_product_related_products_product_id', []);
        $table->addIndex(['related_item_id'], 'idx_oro_product_related_products_related_item_id', []);
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

    private function createUpsellProductTable(Schema $schema)
    {
        $table = $schema->createTable(self::UPSELL_PRODUCTS_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => true]);
        $table->addColumn('related_item_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['product_id'], 'idx_oro_product_upsell_product_product_id', []);
        $table->addIndex(['related_item_id'], 'idx_oro_product_upsell_product_related_item_id', []);
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
    protected function createOroBrandTable(Schema $schema)
    {
        $table = $schema->createTable('oro_brand');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('status', 'string', ['length' => 16]);
        $table->addColumn('default_title', 'string', ['length' => 255, 'notnull' => true]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['created_at'], 'idx_oro_brand_created_at', []);
        $table->addIndex(['updated_at'], 'idx_oro_brand_updated_at', []);
        $table->addIndex(['default_title'], 'idx_oro_brand_default_title', []);
    }

    /**
     * Create oro_brand_description table
     */
    protected function createOroBrandDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable('oro_brand_description');
        $table->addColumn('brand_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['brand_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_E42C1AB4EB576E89');
    }

    /**
     * Add oro_brand foreign keys.
     */
    protected function addBrandForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::BRAND_TABLE_NAME);
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
    protected function createOroBrandNameTable(Schema $schema)
    {
        $table = $schema->createTable('oro_brand_name');
        $table->addColumn('brand_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['brand_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_FA144D83EB576E89');
    }

    /**
     * Create oro_brand_short_desc table
     */
    protected function createOroBrandShortDescTable(Schema $schema)
    {
        $table = $schema->createTable('oro_brand_short_desc');
        $table->addColumn('brand_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['brand_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_70031058EB576E89');
    }

    /**
     * Create oro_brand_slug table
     */
    protected function createOroBrandSlugTable(Schema $schema)
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
    protected function createOroBrandSlugPrototypeTable(Schema $schema)
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
    protected function createOroProductWebsiteReindexRequestItem(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_WEBSITE_REINDEX_REQUEST_ITEM);
        $table->addColumn('related_job_id', 'integer', ['notnull' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => true]);
        $table->addUniqueIndex(['product_id', 'related_job_id', 'website_id'], 'prod_webs_reindex_req_uniq_idx');
    }

    /**
     * Add oro_brand_description foreign keys.
     */
    protected function addOroBrandDescriptionForeignKeys(Schema $schema)
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
    protected function addOroBrandNameForeignKeys(Schema $schema)
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
    protected function addOroBrandShortDescForeignKeys(Schema $schema)
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

    public function addProductToBrand(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_TABLE_NAME);
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
    protected function createCollectionSortOrderTable(Schema $schema): void
    {
        if (!$schema->hasTable(static::PRODUCT_COLLECTION_SORT_ORDER_TABLE_NAME)) {
            $table = $schema->createTable(static::PRODUCT_COLLECTION_SORT_ORDER_TABLE_NAME);
            $table->addColumn('id', 'integer', ['autoincrement' => true]);
            $table->addColumn('sort_order', 'float', [
                'notnull' => false,
                'default' => null
            ]);
            $table->addColumn('product_id', 'integer', ['notnull' => true]);
            $table->addColumn('segment_id', 'integer', ['notnull' => true]);
            $table->setPrimaryKey(['id']);
            $table->addUniqueIndex(
                ['product_id', 'segment_id'],
                'product_segment_sort_uniq_idx'
            );
        }
    }

    /**
     * Add foreign keys to the oro_product_collection_sort_order table
     */
    public function addCollectionSortOrderForeignKeys(Schema $schema) : void
    {
        $table = $schema->getTable(static::PRODUCT_COLLECTION_SORT_ORDER_TABLE_NAME);
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

    protected function createOroProductKitItemTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_product_kit_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');

        $table->addColumn('optional', 'boolean', ['default' => false]);
        $table->addColumn('sort_order', 'integer', ['default' => 0]);
        $table->addColumn('minimum_quantity', 'float', ['notnull' => false]);
        $table->addColumn('maximum_quantity', 'float', ['notnull' => false]);

        $table->addColumn('unit_code', 'string', ['notnull' => false]);
        $table->addColumn('product_kit_id', 'integer', ['notnull' => false]);
    }

    protected function addOroProductKitItemForeignKeys(Schema $schema): void
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

    protected function createOroProductKitItemLabelTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_product_prod_kit_item_label');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_kit_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('localization_id', 'integer', ['notnull' => false]);
        $table->addColumn('fallback', 'string', ['notnull' => false, 'length' => 64]);
        $table->addColumn('string', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['fallback'], 'idx_product_prod_kit_fallback', []);
        $table->addIndex(['string'], 'idx_product_prod_kit_string', []);
    }

    protected function addOroProductKitItemLabelForeignKeys(Schema $schema): void
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

    protected function createOroProductKitItemProductTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_product_kit_item_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_kit_item_id', 'integer', []);
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('product_unit_precision_id', 'integer', ['notnull' => false]);
        $table->addColumn('sort_order', 'integer', ['default' => 0]);
        $table->setPrimaryKey(['id']);
    }

    protected function addOroProductKitItemProductForeignKeys(Schema $schema): void
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
