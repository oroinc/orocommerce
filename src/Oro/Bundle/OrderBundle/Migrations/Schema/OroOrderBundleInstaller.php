<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema;

use Doctrine\DBAL\Platforms\PostgreSqlPlatform;
use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareTrait;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareTrait;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntitySerializedFieldsBundle\Migration\Extension\SerializedFieldsExtensionAwareInterface;
use Oro\Bundle\EntitySerializedFieldsBundle\Migration\Extension\SerializedFieldsExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Extension\DatabasePlatformAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\MigrationBundle\Migration\SqlMigrationQuery;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Migrations\Data\ORM\LoadAdditionalOrderInternalStatuses;
use Oro\Bundle\OrderBundle\Migrations\Data\ORM\LoadOrderInternalStatuses;
use Oro\Bundle\PaymentTermBundle\Migration\Extension\PaymentTermExtensionAwareInterface;
use Oro\Bundle\PaymentTermBundle\Migration\Extension\PaymentTermExtensionAwareTrait;

class OroOrderBundleInstaller implements
    Installation,
    DatabasePlatformAwareInterface,
    AttachmentExtensionAwareInterface,
    ActivityExtensionAwareInterface,
    PaymentTermExtensionAwareInterface,
    ExtendExtensionAwareInterface,
    SerializedFieldsExtensionAwareInterface
{
    use DatabasePlatformAwareTrait;
    use AttachmentExtensionAwareTrait;
    use ActivityExtensionAwareTrait;
    use PaymentTermExtensionAwareTrait;
    use ExtendExtensionAwareTrait;
    use SerializedFieldsExtensionAwareTrait;

    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_25';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroLineItemsTable($schema);
        $this->createOroShippingTrackingsTable($schema);
        $this->createOroOrderTable($schema, $queries);
        $this->createOroOrderAddressTable($schema);
        $this->createOroOrderLineItemTable($schema);
        $this->createOroOrderProductKitItemLineItemTable($schema);
        $this->createOroOrderDiscountTable($schema);
        $this->createOroOrderShippingTrackingTable($schema);

        /** Foreign keys generation **/
        $this->addOroLineItemsForeignKeys($schema);
        $this->addOroShippingTrackingsKeys($schema);
        $this->addOroOrderForeignKeys($schema);
        $this->addOroOrderAddressForeignKeys($schema);
        $this->addOroOrderLineItemForeignKeys($schema);
        $this->addOroOrderProductKitItemLineItemForeignKeys($schema);
        $this->addOroOrderDiscountForeignKeys($schema);

        $this->addOrderInternalStatusField($schema);
        $this->addOrderStatusField($schema);
        $this->addOrderShippingStatusField($schema);

        $this->paymentTermExtension->addPaymentTermAssociation(
            $schema,
            'oro_order',
            ['datagrid' => ['is_visible' => DatagridScope::IS_VISIBLE_HIDDEN, 'show_filter' => false]]
        );
    }

    /**
     * Create oro_order table
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function createOroOrderTable(Schema $schema, QueryBag $queries): void
    {
        $table = $schema->createTable('oro_order');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('created_by_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('shipping_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('billing_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('parent_id', 'integer', ['notnull' => false]);
        $table->addColumn('identifier', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('po_number', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('customer_notes', 'text', ['notnull' => false]);
        $table->addColumn('ship_until', 'date', ['notnull' => false]);
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 3]);
        $table->addColumn('shipping_method', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('shipping_method_type', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn(
            'subtotal_value',
            'money_value',
            ['notnull' => false, 'precision' => 0, 'comment' => '(DC2Type:money_value)']
        );
        $table->addColumn(
            'subtotal_currency',
            'currency',
            ['length' => 3, 'notnull' => false, 'comment' => '(DC2Type:currency)']
        );
        $table->addColumn(
            'base_subtotal_value',
            'money',
            ['notnull' => false, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn(
            'subtotal_with_discounts_value',
            'money_value',
            ['notnull' => false, 'precision' => 0, 'comment' => '(DC2Type:money_value)']
        );
        $table->addColumn(
            'subtotal_with_discounts_currency',
            'currency',
            ['length' => 3, 'notnull' => false, 'comment' => '(DC2Type:currency)']
        );
        $table->addColumn(
            'subtotal_with_discounts', // Base subtotal with discount.
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn(
            'total_value',
            'money_value',
            ['notnull' => false, 'precision' => 0, 'comment' => '(DC2Type:money_value)']
        );
        $table->addColumn(
            'total_currency',
            'currency',
            ['length' => 3, 'notnull' => false, 'comment' => '(DC2Type:currency)']
        );
        $table->addColumn(
            'base_total_value',
            'money',
            ['notnull' => false, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn(
            'estimated_shipping_cost_amount',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn(
            'override_shipping_cost_amount',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn(
            'total_discounts_amount',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn('customer_id', 'integer', ['notnull' => false]);
        $table->addColumn('customer_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('source_entity_class', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('source_entity_id', 'integer', ['notnull' => false]);
        $table->addColumn('source_entity_identifier', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('is_external', 'boolean', ['default' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['created_at'], 'oro_order_created_at_index');
        $table->addUniqueIndex(['identifier'], 'uniq_oro_order_identifier');
        $table->addUniqueIndex(['shipping_address_id'], 'uniq_c036ff904d4cff2b');
        $table->addUniqueIndex(['billing_address_id'], 'uniq_c036ff9079d0c0e4');

        $this->activityExtension->addActivityAssociation($schema, 'oro_note', $table->getName());
        $this->attachmentExtension->addAttachmentAssociation($schema, $table->getName());
        $this->activityExtension->addActivityAssociation($schema, 'oro_email', $table->getName());

        if ($this->platform instanceof PostgreSqlPlatform) {
            $queries->addPostQuery(new SqlMigrationQuery(
                'CREATE INDEX idx_order_identifier_ci ON oro_order (LOWER(identifier))'
            ));
            $queries->addPostQuery(new SqlMigrationQuery(
                'CREATE INDEX idx_order_po_number_ci ON oro_order (LOWER(po_number))'
            ));
        }
    }

    /**
     * Create oro_order_address table
     */
    private function createOroOrderAddressTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_order_address');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('customer_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('customer_user_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('region_code', 'string', ['notnull' => false, 'length' => 16]);
        $table->addColumn('country_code', 'string', ['notnull' => false, 'length' => 2]);
        $table->addColumn('label', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('street', 'string', ['notnull' => false, 'length' => 500]);
        $table->addColumn('street2', 'string', ['notnull' => false, 'length' => 500]);
        $table->addColumn('city', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('postal_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('organization', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('region_text', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('name_prefix', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('first_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('middle_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('last_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('name_suffix', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('phone', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('from_external_source', 'boolean', ['notnull' => true, 'default' => false]);
        $table->addColumn('validated_at', 'datetime', ['notnull' => false]);
        $table->addColumn('created', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_order_discount table
     */
    private function createOroOrderDiscountTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_order_discount');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('order_id', 'integer', ['notnull' => true]);
        $table->addColumn('description', 'text', ['notnull' => false]);
        $table->addColumn('type', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn(
            'percent',
            'percent',
            ['notnull' => false, 'precision' => 0, 'comment' => '(DC2Type:percent)']
        );
        $table->addColumn(
            'amount',
            'money',
            ['notnull' => true, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->setPrimaryKey(['id']);
        $table->addIndex(['order_id'], 'IDX_F9A53B6A8D9F6D38');
    }

    /**
     * Create oro_order_line_item table
     */
    private function createOroOrderLineItemTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_order_line_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_unit_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('parent_product_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_sku', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('product_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('product_variant_fields', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('free_form_product', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('quantity', 'float', ['notnull' => false]);
        $table->addColumn('product_unit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn(
            'value',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('price_type', 'integer');
        $table->addColumn('ship_by', 'date', ['notnull' => false, 'comment' => '(DC2Type:date)']);
        $table->addColumn('from_external_source', 'boolean');
        $table->addColumn('comment', 'text', ['notnull' => false]);
        $table->addColumn('shipping_method', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('shipping_method_type', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('shipping_estimate_amount', 'money', [
            'notnull' => false,
            'precision' => 19,
            'scale' => 4,
            'comment' => '(DC2Type:money)'
        ]);
        $table->addColumn('checksum', 'string', ['length' => 40, 'notnull' => true, 'default' => '']);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['product_id'], 'idx_de9136094584665a');
        $table->addIndex(['product_unit_id'], 'idx_de91360929646bbd');
    }

    /**
     * Create oro_order_shipping_tracking table
     */
    private function createOroOrderShippingTrackingTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_order_shipping_tracking');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('method', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('number', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_order foreign keys.
     */
    private function addOroOrderForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_order');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['created_by_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order_address'),
            ['shipping_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order_address'),
            ['billing_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer'),
            ['customer_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user'),
            ['customer_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $table,
            ['parent_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_order_address foreign keys.
     */
    private function addOroOrderAddressForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_order_address');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_address'),
            ['customer_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_customer_user_address'),
            ['customer_user_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_region'),
            ['region_code'],
            ['combined_code'],
            ['onUpdate' => null, 'onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_dictionary_country'),
            ['country_code'],
            ['iso2_code'],
            ['onUpdate' => null, 'onDelete' => null]
        );
    }

    /**
     * Add oro_order_line_item foreign keys.
     */
    private function addOroOrderLineItemForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_order_line_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit'),
            ['product_unit_id'],
            ['code'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['parent_product_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_order_discount foreign keys.
     */
    private function addOroOrderDiscountForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_order_discount');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order'),
            ['order_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    private function addOrderInternalStatusField(Schema $schema): void
    {
        $this->extendExtension->addEnumField(
            $schema,
            'oro_order',
            'internal_status',
            Order::INTERNAL_STATUS_CODE,
            false,
            false,
            [
                'dataaudit' => ['auditable' => true],
                'enum' => [
                    'immutable_codes' => ExtendHelper::mapToEnumOptionIds(Order::INTERNAL_STATUS_CODE, array_merge(
                        LoadOrderInternalStatuses::getDataKeys(),
                        LoadAdditionalOrderInternalStatuses::getDataKeys()
                    ))
                ]
            ]
        );
    }

    private function addOrderStatusField(Schema $schema): void
    {
        $this->extendExtension->addEnumField(
            $schema,
            'oro_order',
            'status',
            Order::STATUS_CODE,
            false,
            false,
            ['dataaudit' => ['auditable' => true]]
        );
    }

    private function addOrderShippingStatusField(Schema $schema): void
    {
        $this->extendExtension->addEnumField(
            $schema,
            'oro_order',
            'shippingStatus',
            Order::SHIPPING_STATUS_CODE,
            false,
            false,
            [
                'dataaudit' => ['auditable' => true],
                'enum' => [
                    'immutable_codes' => ExtendHelper::mapToEnumOptionIds(
                        Order::SHIPPING_STATUS_CODE,
                        ['not_shipped', 'shipped']
                    )
                ]
            ]
        );
    }

    private function createOroOrderProductKitItemLineItemTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_order_product_kit_item_line_item');
        $table->addColumn('id', 'integer', ['notnull' => true, 'autoincrement' => true]);
        $table->addColumn('line_item_id', 'integer', ['notnull' => true]);
        $table->addColumn('product_kit_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_kit_item_id_fallback', 'integer', ['notnull' => true]);
        $table->addColumn('product_kit_item_label', 'string', ['notnull' => true, 'length' => 255]);
        $table->addColumn('optional', 'boolean', ['notnull' => true, 'default' => false]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_id_fallback', 'integer', ['notnull' => true]);
        $table->addColumn('product_sku', 'string', ['notnull' => true, 'length' => 255]);
        $table->addColumn('product_name', 'string', ['notnull' => true, 'length' => 255]);
        $table->addColumn('product_unit_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('product_unit_code', 'string', ['notnull' => true, 'length' => 255]);
        $table->addColumn('product_unit_precision', 'integer', ['notnull' => true]);
        $table->addColumn('quantity', 'float', ['notnull' => true]);
        $table->addColumn('minimum_quantity', 'float', ['notnull' => false]);
        $table->addColumn('maximum_quantity', 'float', ['notnull' => false]);
        $table->addColumn('sort_order', 'integer', ['notnull' => true, 'default' => 0]);
        $table->addColumn(
            'value',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    private function addOroOrderProductKitItemLineItemForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_order_product_kit_item_line_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order_line_item'),
            ['line_item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_kit_item'),
            ['product_kit_item_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit'),
            ['product_unit_id'],
            ['code'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }

    private function createOroLineItemsTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_order_line_items');
        $table->addColumn('order_id', 'integer');
        $table->addColumn('line_item_id', 'integer');
        $table->setPrimaryKey(['line_item_id', 'order_id']);
        $table->addIndex(['order_id'], 'IDX_order_id395');
        $table->addIndex(['line_item_id'], 'IDX_line_item_id2AC');
    }

    private function createOroShippingTrackingsTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_order_shipping_trackings');
        $table->addColumn('order_id', 'integer');
        $table->addColumn('tracking_id', 'integer');
        $table->setPrimaryKey(['tracking_id', 'order_id']);
        $table->addIndex(['order_id'], 'IDX_order_id454');
        $table->addIndex(['tracking_id'], 'IDX_line_item_id2AT');
    }

    private function addOroLineItemsForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_order_line_items');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order'),
            ['order_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order_line_item'),
            ['line_item_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }

    private function addOroShippingTrackingsKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_order_shipping_trackings');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order'),
            ['order_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order_shipping_tracking'),
            ['tracking_id'],
            ['id'],
            ['onDelete' => 'CASCADE']
        );
    }
}
