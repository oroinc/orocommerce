<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroB2BProductBundleInstaller implements
    Installation,
    ExtendExtensionAwareInterface,
    NoteExtensionAwareInterface,
    AttachmentExtensionAwareInterface
{
    const PRODUCT_TABLE_NAME = 'orob2b_product';
    const PRODUCT_UNIT_TABLE_NAME = 'orob2b_product_unit';
    const PRODUCT_UNIT_PRECISION_TABLE_NAME = 'orob2b_product_unit_precision';
    const PRODUCT_VARIANT_LINK_TABLE_NAME = 'orob2b_product_variant_link';
    const PRODUCT_SHORT_DESCRIPTION_TABLE_NAME = 'orob2b_product_short_desc';
    const FALLBACK_LOCALE_VALUE_TABLE_NAME = 'oro_fallback_localization_val';

    const MAX_PRODUCT_IMAGE_SIZE_IN_MB = 10;
    const MAX_PRODUCT_ATTACHMENT_SIZE_IN_MB = 5;

    const PRODUCT_IMAGE_TABLE_NAME = 'orob2b_product_image';
    const PRODUCT_IMAGE_TYPE_TABLE_NAME = 'orob2b_product_image_type';

    /** @var ExtendExtension */
    protected $extendExtension;

    /** @var NoteExtension */
    protected $noteExtension;

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
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function setNoteExtension(NoteExtension $noteExtension)
    {
        $this->noteExtension = $noteExtension;
    }

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_5';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroB2BProductTable($schema);
        $this->createOroB2BProductUnitTable($schema);
        $this->createOroB2BProductUnitPrecisionTable($schema);
        $this->createOrob2BProductNameTable($schema);
        $this->createOrob2BProductDescriptionTable($schema);
        $this->createOroB2BProductVariantLinkTable($schema);
        $this->createOroB2BProductShortDescriptionTable($schema);
        $this->createOroB2BProductImageTable($schema);
        $this->createOroB2BProductImageTypeTable($schema);

        $this->addOroB2BProductForeignKeys($schema);
        $this->addOroB2BProductUnitPrecisionForeignKeys($schema);
        $this->addOrob2BProductNameForeignKeys($schema);
        $this->addOrob2BProductDescriptionForeignKeys($schema);
        $this->addOroB2BProductVariantLinkForeignKeys($schema);
        $this->addOroB2BProductShortDescriptionForeignKeys($schema);
        $this->addOroB2BProductImageForeignKeys($schema);
        $this->addOroB2BProductImageTypeForeignKeys($schema);

        $this->updateProductTable($schema);
        $this->addNoteAssociations($schema);
        $this->addAttachmentAssociations($schema);
    }

    /**
     * Create orob2b_product table
     *
     * @param Schema $schema
     */
    protected function createOroB2BProductTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('sku', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('has_variants', 'boolean', ['default' => false]);
        $table->addColumn('variant_fields', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('status', 'string', ['length' => 16]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['sku']);
        $table->addIndex(['created_at'], 'idx_orob2b_product_created_at', []);
        $table->addIndex(['updated_at'], 'idx_orob2b_product_updated_at', []);
        $table->addIndex(['sku'], 'idx_orob2b_product_sku', []);
    }

    /**
     * Create orob2b_product_unit table
     *
     * @param Schema $schema
     */
    protected function createOroB2BProductUnitTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_UNIT_TABLE_NAME);
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('default_precision', 'integer');
        $table->setPrimaryKey(['code']);
    }

    /**
     * Create orob2b_product_unit_precision table
     *
     * @param Schema $schema
     */
    protected function createOroB2BProductUnitPrecisionTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_UNIT_PRECISION_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('unit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('unit_precision', 'integer', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['product_id', 'unit_code'], 'product_unit_precision__product_id__unit_code__uidx');
    }

    /**
     * @param Schema $schema
     */
    protected function createOrob2BProductNameTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_product_name');
        $table->addColumn('product_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['product_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'uniq_ba57d521eb576e89');
    }

    /**
     * @param Schema $schema
     */
    protected function createOrob2BProductDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_product_description');
        $table->addColumn('description_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['description_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'uniq_416a3679eb576e89');
    }

    /**
     * Add orob2b_product foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BProductForeignKeys(Schema $schema)
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
    }

    /**
     * Add orob2b_product_unit_precision foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BProductUnitPrecisionForeignKeys(Schema $schema)
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

    /**
     * @param Schema $schema
     */
    protected function addOrob2BProductNameForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_product_name');
        $table->addForeignKeyConstraint(
            $schema->getTable(self::FALLBACK_LOCALE_VALUE_TABLE_NAME),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addOrob2BProductDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_product_description');
        $table->addForeignKeyConstraint(
            $schema->getTable(self::FALLBACK_LOCALE_VALUE_TABLE_NAME),
            ['localized_value_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['description_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
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
            ]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addNoteAssociations(Schema $schema)
    {
        $this->noteExtension->addNoteAssociation($schema, self::PRODUCT_TABLE_NAME);
    }

    /**
     * @param Schema $schema
     */
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
                'importexport' => ['excluded' => true]
            ],
            self::MAX_PRODUCT_IMAGE_SIZE_IN_MB
        );
    }

    /**
     * @param Schema $schema
     */
    protected function createOroB2BProductVariantLinkTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_VARIANT_LINK_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => true]);
        $table->addColumn('parent_product_id', 'integer', ['notnull' => true]);
        $table->addColumn('visible', 'boolean', ['default' => true]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function addOroB2BProductVariantLinkForeignKeys(Schema $schema)
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

    /**
     * @param Schema $schema
     */
    protected function createOroB2BProductShortDescriptionTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_SHORT_DESCRIPTION_TABLE_NAME);
        $table->addColumn('short_description_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['short_description_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id']);
    }

    /**
     * @param Schema $schema
     */
    protected function addOroB2BProductShortDescriptionForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_SHORT_DESCRIPTION_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::FALLBACK_LOCALE_VALUE_TABLE_NAME),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_TABLE_NAME),
            ['short_description_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * @param Schema $schema
     */
    protected function createOroB2BProductImageTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_IMAGE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => true]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function createOroB2BProductImageTypeTable(Schema $schema)
    {
        $table = $schema->createTable(self::PRODUCT_IMAGE_TYPE_TABLE_NAME);
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_image_id', 'integer');
        $table->addColumn('type', 'string', ['length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * @param Schema $schema
     */
    protected function addOroB2BProductImageForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_IMAGE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_TABLE_NAME),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * @param Schema $schema
     */
    protected function addOroB2BProductImageTypeForeignKeys(Schema $schema)
    {
        $table = $schema->getTable(self::PRODUCT_IMAGE_TYPE_TABLE_NAME);
        $table->addForeignKeyConstraint(
            $schema->getTable(self::PRODUCT_IMAGE_TABLE_NAME),
            ['product_image_id'],
            ['id'],
            ['onDelete' => null, 'onUpdate' => null]
        );
    }
}
