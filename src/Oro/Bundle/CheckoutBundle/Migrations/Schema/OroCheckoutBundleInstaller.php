<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareTrait;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroCheckoutBundleInstaller implements Installation, ExtendExtensionAwareInterface
{
    use ExtendExtensionAwareTrait;

    #[\Override]
    public function getMigrationVersion(): string
    {
        return 'v1_16';
    }

    #[\Override]
    public function up(Schema $schema, QueryBag $queries): void
    {
        /** Tables generation **/
        $this->createOroCheckoutSourceTable($schema);
        $this->createOroCheckoutTable($schema);
        $this->createCheckoutWorkflowStateTable($schema);
        $this->createOroCheckoutLineItemTable($schema);
        $this->createOroCheckoutProductKitItemLineItemTable($schema);
        $this->createOroCheckoutSubtotalTable($schema);

        /** Foreign keys generation **/
        $this->addOroCheckoutForeignKeys($schema);
        $this->addOroCheckoutLineItemForeignKeys($schema);
        $this->addOroCheckoutProductKitItemLineItemForeignKeys($schema);
        $this->addOroCheckoutSubtotalForeignKeys($schema);

        $this->addOrderCheckoutSource($schema);
    }

    /**
     * Create oro_checkout_source table
     */
    private function createOroCheckoutSourceTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_checkout_source');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('deleted', 'boolean', ['default' => false]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_checkout table
     */
    private function createOroCheckoutTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_checkout');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('source_id', 'integer', ['notnull' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('customer_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('registered_customer_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('customer_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('po_number', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('customer_notes', 'text', ['notnull' => false]);
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 3]);
        $table->addColumn('ship_until', 'date', ['notnull' => false, 'comment' => '(DC2Type:date)']);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('shipping_estimate_amount', 'money', [
            'notnull' => false,
            'precision' => 19,
            'scale' => 4,
            'comment' => '(DC2Type:money)'
        ]);
        $table->addColumn('shipping_estimate_currency', 'string', ['notnull' => false, 'length' => 3]);
        $table->addColumn('payment_method', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('billing_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('shipping_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('save_billing_address', 'boolean', ['default' => true]);
        $table->addColumn('ship_to_billing_address', 'boolean', ['default' => false]);
        $table->addColumn('save_shipping_address', 'boolean', ['default' => true]);
        $table->addColumn('shipping_method', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('shipping_method_type', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('deleted', 'boolean', ['default' => false]);
        $table->addColumn('completed', 'boolean', ['default' => false]);
        $table->addColumn('completed_data', 'json_array', ['comment' => '(DC2Type:json_array)']);
        $table->addColumn('line_item_group_shipping_data', 'json', ['notnull' => false, 'comment' => '(DC2Type:json)']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['billing_address_id'], 'uniq_checkout_bill_addr');
        $table->addUniqueIndex(['shipping_address_id'], 'uniq_checkout_shipp_addr');
        $table->addUniqueIndex(['source_id'], 'uniq_e56b559d953c1c61');
        $table->addUniqueIndex(['registered_customer_user_id'], 'UNIQ_C040FD5916A5A0D');
    }

    /**
     * Add oro_checkout foreign keys.
     */
    private function addOroCheckoutForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_checkout');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_checkout_source'),
            ['source_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
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
            $schema->getTable('oro_customer_user'),
            ['registered_customer_user_id'],
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
            $schema->getTable('oro_order_address'),
            ['billing_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order_address'),
            ['shipping_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    private function createCheckoutWorkflowStateTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_checkout_workflow_state');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('token', 'string', ['length' => 255]);
        $table->addColumn('entity_id', 'integer');
        $table->addColumn('entity_class', 'string', ['length' => 255]);
        $table->addColumn('state_data', 'array', ['comment' => '(DC2Type:array)']);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addUniqueIndex(['entity_id', 'entity_class', 'token'], 'oro_checkout_wf_state_uidx');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_checkout_subtotal table
     */
    private function createOroCheckoutSubtotalTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_checkout_subtotal');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('checkout_id', 'integer');
        $table->addColumn('currency', 'string', ['length' => 255]);
        $table->addColumn(
            'value',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn('combined_price_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('price_list_id', 'integer', ['notnull' => false]);
        $table->addColumn('is_valid', 'boolean');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['checkout_id', 'currency'], 'unique_checkout_currency');
        $table->addIndex(['is_valid'], 'idx_checkout_subtotal_valid');
    }

    /**
     * Create oro_checkout_line_item table
     */
    private function createOroCheckoutLineItemTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_checkout_line_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('checkout_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('parent_product_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_unit_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('product_sku', 'string', ['notnull' => false, 'length' => 255]);
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
        $table->addColumn('from_external_source', 'boolean');
        $table->addColumn('comment', 'text', ['notnull' => false]);
        $table->addColumn('is_price_fixed', 'boolean');
        $table->addColumn('shipping_method', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('shipping_method_type', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('shipping_estimate_amount', 'money', [
            'notnull' => false,
            'precision' => 19,
            'scale' => 4,
            'comment' => '(DC2Type:money)'
        ]);
        $table->addColumn('checksum', 'string', ['length' => 40, 'notnull' => true, 'default' => '']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_checkout_line_item foreign keys.
     */
    private function addOroCheckoutLineItemForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_checkout_line_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_checkout'),
            ['checkout_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
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
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit'),
            ['product_unit_id'],
            ['code'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add oro_checkout_subtotal foreign keys.
     */
    private function addOroCheckoutSubtotalForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_checkout_subtotal');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_checkout'),
            ['checkout_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list_combined'),
            ['combined_price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_price_list'),
            ['price_list_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    private function addOrderCheckoutSource(Schema $schema): void
    {
        if (class_exists('Oro\Bundle\OrderBundle\Entity\Order')) {
            $this->extendExtension->addManyToOneRelation(
                $schema,
                'oro_checkout_source',
                'order',
                'oro_order',
                'id',
                [
                    'entity' => ['label' => 'oro.order.entity_label'],
                    'extend' => [
                        'is_extend' => true,
                        'owner' => ExtendScope::OWNER_CUSTOM
                    ],
                    'datagrid' => [
                        'is_visible' => DatagridScope::IS_VISIBLE_FALSE,
                    ],
                    'form' => [
                        'is_enabled' => false
                    ],
                    'view' => ['is_displayable' => false],
                    'merge' => ['display' => false],
                    'dataaudit' => ['auditable' => false]
                ]
            );
        }
    }

    private function createOroCheckoutProductKitItemLineItemTable(Schema $schema): void
    {
        $table = $schema->createTable('oro_checkout_product_kit_item_line_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('line_item_id', 'integer');
        $table->addColumn('product_kit_item_id', 'integer');
        $table->addColumn('product_id', 'integer');
        $table->addColumn('product_unit_id', 'string', ['length' => 255]);
        $table->addColumn('quantity', 'float');
        $table->addColumn('sort_order', 'integer', ['default' => 0]);
        $table->addColumn(
            'value',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('is_price_fixed', 'boolean', ['notnull' => true, 'default' => false]);
        $table->setPrimaryKey(['id']);
    }

    private function addOroCheckoutProductKitItemLineItemForeignKeys(Schema $schema): void
    {
        $table = $schema->getTable('oro_checkout_product_kit_item_line_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_checkout_line_item'),
            ['line_item_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
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
            $schema->getTable('oro_product_unit'),
            ['product_unit_id'],
            ['code'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }
}
