<?php

namespace Oro\Bundle\OrderBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtension;
use Oro\Bundle\ActivityBundle\Migration\Extension\ActivityExtensionAwareInterface;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtension;
use Oro\Bundle\AttachmentBundle\Migration\Extension\AttachmentExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtension;
use Oro\Bundle\NoteBundle\Migration\Extension\NoteExtensionAwareInterface;

class OroOrderBundleInstaller implements
    Installation,
    NoteExtensionAwareInterface,
    AttachmentExtensionAwareInterface,
    ActivityExtensionAwareInterface
{
    /** @var  NoteExtension */
    protected $noteExtension;

    /** @var  AttachmentExtension */
    protected $attachmentExtension;

    /** @var  ActivityExtension */
    protected $activityExtension;

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
    public function setAttachmentExtension(AttachmentExtension $attachmentExtension)
    {
        $this->attachmentExtension = $attachmentExtension;
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
        return 'v1_7';
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroOrderTable($schema);
        $this->createOroOrderAddressTable($schema);
        $this->createOroOrderLineItemTable($schema);
        $this->createOroOrderDiscountTable($schema);
        $this->createOroOrderShippingTrackingTable($schema);

        /** Foreign keys generation **/
        $this->addOroOrderForeignKeys($schema);
        $this->addOroOrderAddressForeignKeys($schema);
        $this->addOroOrderLineItemForeignKeys($schema);
        $this->addOroOrderDiscountForeignKeys($schema);
        $this->addOroOrderShippingTrackingForeignKeys($schema);
    }

    /**
     * Create oro_order table
     *
     * @param Schema $schema
     */
    protected function createOroOrderTable(Schema $schema)
    {
        $table = $schema->createTable('oro_order');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('shipping_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('billing_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
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
            'subtotal',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn(
            'total',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn(
            'shipping_cost_amount',
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
        $table->addColumn('payment_term_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('source_entity_class', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('source_entity_id', 'integer', ['notnull' => false]);
        $table->addColumn('source_entity_identifier', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['created_at'], 'oro_order_created_at_index', []);
        $table->addUniqueIndex(['identifier'], 'uniq_oro_order_identifier');
        $table->addUniqueIndex(['shipping_address_id'], 'uniq_c036ff904d4cff2b');
        $table->addUniqueIndex(['billing_address_id'], 'uniq_c036ff9079d0c0e4');

        $this->noteExtension->addNoteAssociation($schema, $table->getName());
        $this->attachmentExtension->addAttachmentAssociation($schema, $table->getName());
        $this->activityExtension->addActivityAssociation($schema, 'oro_calendar_event', $table->getName());
        $this->activityExtension->addActivityAssociation($schema, 'oro_email', $table->getName());
    }

    /**
     * Create oro_order_address table
     *
     * @param Schema $schema
     */
    protected function createOroOrderAddressTable(Schema $schema)
    {
        $table = $schema->createTable('oro_order_address');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('account_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_user_address_id', 'integer', ['notnull' => false]);
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
        $table->addColumn('created', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_order_discount table
     *
     * @param Schema $schema
     */
    protected function createOroOrderDiscountTable(Schema $schema)
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
        $table->addIndex(['order_id'], 'IDX_F9A53B6A8D9F6D38', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_order_line_item table
     *
     * @param Schema $schema
     */
    protected function createOroOrderLineItemTable(Schema $schema)
    {
        $table = $schema->createTable('oro_order_line_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_unit_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('order_id', 'integer', ['notnull' => false]);
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
        $table->addColumn('price_type', 'integer', []);
        $table->addColumn('ship_by', 'date', ['notnull' => false, 'comment' => '(DC2Type:date)']);
        $table->addColumn('from_external_source', 'boolean', []);
        $table->addColumn('comment', 'text', ['notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['product_id'], 'idx_de9136094584665a', []);
        $table->addIndex(['product_unit_id'], 'idx_de91360929646bbd', []);
        $table->addIndex(['order_id'], 'idx_de9136098d9f6d38', []);
    }

    /**
     * Create oro_order_shipping_tracking table
     *
     * @param Schema $schema
     */
    protected function createOroOrderShippingTrackingTable(Schema $schema)
    {
        $table = $schema->createTable('oro_order_shipping_tracking');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('order_id', 'integer', ['notnull' => true]);
        $table->addColumn('method', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('number', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_order foreign keys.
     *
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addOroOrderForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_order');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_payment_term'),
            ['payment_term_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
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
            $schema->getTable('oro_account'),
            ['account_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account_user'),
            ['account_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add oro_order_address foreign keys.
     *
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addOroOrderAddressForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_order_address');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account_address'),
            ['account_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account_user_address'),
            ['account_user_address_id'],
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
     *
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addOroOrderLineItemForeignKeys(Schema $schema)
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
            $schema->getTable('oro_order'),
            ['order_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_order_discount foreign keys.
     *
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addOroOrderDiscountForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_order_discount');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order'),
            ['order_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }

    /**
     * Add oro_order_shipping_tracking foreign keys.
     *
     * @param Schema $schema
     *
     * @throws \Doctrine\DBAL\Schema\SchemaException
     */
    protected function addOroOrderShippingTrackingForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_order_shipping_tracking');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_order'),
            ['order_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
