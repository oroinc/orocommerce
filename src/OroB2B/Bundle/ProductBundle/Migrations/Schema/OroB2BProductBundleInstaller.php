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

class OroB2BProductBundleInstaller implements
    Installation,
    ExtendExtensionAwareInterface,
    NoteExtensionAwareInterface,
    AttachmentExtensionAwareInterface
{
    const PRODUCT_TABLE_NAME = 'orob2b_product';
    const PRODUCT_UNIT_TABLE_NAME = 'orob2b_product_unit';
    const PRODUCT_UNIT_PRECISION_TABLE_NAME = 'orob2b_product_unit_precision';

    const MAX_PRODUCT_IMAGE_SIZE_IN_MB = 10;
    const MAX_PRODUCT_ATTACHMENT_SIZE_IN_MB = 5;

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
        return 'v1_1';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroB2BProductTable($schema);
        $this->createOroB2BProductUnitTable($schema);
        $this->createOroB2BProductUnitPrecisionTable($schema);

        $this->addOroB2BProductForeignKeys($schema);
        $this->addOroB2BProductUnitPrecisionForeignKeys($schema);

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
        $table->addColumn('category_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('sku', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['sku'], 'UNIQ_5F9796C9F9038C4');
        $table->addIndex(['business_unit_owner_id'], 'IDX_5F9796C959294170', []);
        $table->addIndex(['organization_id'], 'IDX_5F9796C932C8A3DE', []);
        $table->addIndex(['category_id'], 'IDX_5F9796C912469DE2', []);
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
        $table->addIndex(['product_id'], 'IDX_47015B824584665A', []);
        $table->addIndex(['unit_code'], 'IDX_47015B82FBD3D1C2', []);
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
            $schema->getTable('orob2b_catalog_category'),
            ['category_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
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
    protected function updateProductTable(Schema $schema)
    {
        $this->extendExtension->addEnumField(
            $schema,
            self::PRODUCT_TABLE_NAME,
            'inventory_status',
            'prod_inventory_status'
        );

        $this->extendExtension->addEnumField(
            $schema,
            self::PRODUCT_TABLE_NAME,
            'visibility',
            'prod_visibility'
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
        $this->attachmentExtension->addImageRelation(
            $schema,
            self::PRODUCT_TABLE_NAME,
            'image',
            [],
            self::MAX_PRODUCT_IMAGE_SIZE_IN_MB
        );

        $this->attachmentExtension->addAttachmentAssociation(
            $schema,
            self::PRODUCT_TABLE_NAME,
            [],
            self::MAX_PRODUCT_ATTACHMENT_SIZE_IN_MB
        );
    }
}
