<?php

namespace OroB2B\Bundle\CheckoutBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroB2BCheckoutBundleInstaller implements Installation
{
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
        /** Tables generation **/
        $this->createOroB2BCheckoutSourceTable($schema);
        $this->createOroB2BCheckoutTable($schema);
        $this->createOroB2BDefaultCheckoutTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BCheckoutForeignKeys($schema);
        $this->addOroB2BDefaultCheckoutForeignKeys($schema);
    }

    /**
     * Create orob2b_checkout_source table
     *
     * @param Schema $schema
     */
    protected function createOroB2BCheckoutSourceTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_checkout_source');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create orob2b_checkout table
     *
     * @param Schema $schema
     */
    protected function createOroB2BCheckoutTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_checkout');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('source_id', 'integer', ['notnull' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('po_number', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('checkout_type', 'string', ['notnull' => false, 'length' => 30]);
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
        $table->addColumn('checkout_discriminator', 'string', ['notnull' => true, 'length' => 30]);
        $table->addUniqueIndex(['source_id'], 'uniq_e56b559d953c1c61');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_checkout foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BCheckoutForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_checkout');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_checkout_source'),
            ['source_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account'),
            ['account_id'],
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
    }

    /**
     * Create orob2b_default_checkout table
     *
     * @param Schema $schema
     */
    protected function createOroB2BDefaultCheckoutTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_default_checkout');

        $table->addColumn('id', 'integer', ['notnull' => true]);
        $table->addColumn('order_id', 'integer', ['notnull' => false]);
        $table->addColumn('billing_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('shipping_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('save_billing_address', 'boolean', []);
        $table->addColumn('ship_to_billing_address', 'boolean', []);
        $table->addColumn('save_shipping_address', 'boolean', []);

        $table->setPrimaryKey(['id']);

        $table->addUniqueIndex(['billing_address_id'], 'uniq_def_checkout_bill_addr');
        $table->addUniqueIndex(['shipping_address_id'], 'uniq_def_checkout_shipp_addr');
        $table->addUniqueIndex(['order_id'], 'uniq_def_checkout_order');
    }

    /**
     * Add orob2b_default_checkout foreign keys
     *
     * @param Schema $schema
     */
    protected function addOroB2BDefaultCheckoutForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_default_checkout');

        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_checkout'),
            ['id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_order'),
            ['order_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_order_address'),
            ['billing_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_order_address'),
            ['shipping_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
