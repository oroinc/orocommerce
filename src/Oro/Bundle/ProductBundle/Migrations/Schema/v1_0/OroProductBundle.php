<?php

namespace Oro\Bundle\ProductBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareTrait;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class OroProductBundle implements
    Migration,
    ExtendExtensionAwareInterface,
    ActivityExtensionAwareInterface,
    AttachmentExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;
    use ActivityExtensionAwareTrait;
    use AttachmentExtensionAwareTrait;

    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries): void
    {
        $this->createOroProductTable($schema);
        $this->createOroProductUnitTable($schema);
        $this->createOroProductUnitPrecisionTable($schema);
        $this->createOrob2BProductNameTable($schema);
        $this->createOrob2BProductDescriptionTable($schema);

        $this->addOroProductForeignKeys($schema);
        $this->addOroProductUnitPrecisionForeignKeys($schema);
        $this->addOrob2BProductNameForeignKeys($schema);
        $this->addOrob2BProductDescriptionForeignKeys($schema);

        $this->extendExtension->addEnumField($schema, 'orob2b_product', 'inventory_status', 'prod_inventory_status');
        $this->extendExtension->addEnumField($schema, 'orob2b_product', 'visibility', 'prod_visibility');
        $this->extendExtension->addEnumField($schema, 'orob2b_product', 'status', 'prod_status');

        $this->activityExtension->addActivityAssociation($schema, 'oro_note', 'orob2b_product');
        $this->attachmentExtension->addImageRelation($schema, 'orob2b_product', 'image', [], 10);
        $this->attachmentExtension->addAttachmentAssociation($schema, 'orob2b_product', [], 5);
    }

    /**
     * Create orob2b_product table
     */
    private function createOroProductTable(Schema $schema): void
    {
        $table = $schema->createTable('orob2b_product');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('business_unit_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('sku', 'string', ['length' => 255]);
        $table->addColumn('created_at', 'datetime');
        $table->addColumn('updated_at', 'datetime');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['sku']);
        $table->addIndex(['created_at'], 'idx_orob2b_product_created_at');
        $table->addIndex(['updated_at'], 'idx_orob2b_product_updated_at');
    }

    /**
     * Create orob2b_product_unit table
     */
    private function createOroProductUnitTable(Schema $schema): void
    {
        $table = $schema->createTable('orob2b_product_unit');
        $table->addColumn('code', 'string', ['length' => 255]);
        $table->addColumn('default_precision', 'integer');
        $table->setPrimaryKey(['code']);
    }

    /**
     * Create orob2b_product_unit_precision table
     */
    private function createOroProductUnitPrecisionTable(Schema $schema): void
    {
        $table = $schema->createTable('orob2b_product_unit_precision');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('unit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('unit_precision', 'integer');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['product_id', 'unit_code'], 'product_unit_precision__product_id__unit_code__uidx');
    }

    private function createOrob2BProductNameTable(Schema $schema): void
    {
        $table = $schema->createTable('orob2b_product_name');
        $table->addColumn('product_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['product_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'uniq_ba57d521eb576e89');
    }

    private function createOrob2BProductDescriptionTable(Schema $schema): void
    {
        $table = $schema->createTable('orob2b_product_description');
        $table->addColumn('description_id', 'integer');
        $table->addColumn('localized_value_id', 'integer');
        $table->setPrimaryKey(['description_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'uniq_416a3679eb576e89');
    }

    /**
     * Add orob2b_product foreign keys.
     */
    private function addOroProductForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('orob2b_product');
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
     */
    private function addOroProductUnitPrecisionForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('orob2b_product_unit_precision');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product_unit'),
            ['unit_code'],
            ['code'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    private function addOrob2BProductNameForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('orob2b_product_name');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_fallback_locale_value'),
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

    private function addOrob2BProductDescriptionForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('orob2b_product_description');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_fallback_locale_value'),
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
}
