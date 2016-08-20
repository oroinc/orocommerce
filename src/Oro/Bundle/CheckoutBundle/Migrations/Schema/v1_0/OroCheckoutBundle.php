<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroCheckoutBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOrob2BCheckoutSourceTable($schema);
        $this->createOrob2BCheckoutTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BCheckoutForeignKeys($schema);
    }

    /**
     * Create orob2b_checkout_source table
     *
     * @param Schema $schema
     */
    protected function createOrob2BCheckoutSourceTable(Schema $schema)
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
    protected function createOrob2BCheckoutTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_checkout');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('workflow_step_id', 'integer', ['notnull' => false]);
        $table->addColumn('workflow_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('source_id', 'integer', ['notnull' => true]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('shipping_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('billing_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('order_id', 'integer', ['notnull' => false]);
        $table->addColumn('save_billing_address', 'boolean', []);
        $table->addColumn('ship_to_billing_address', 'boolean', []);
        $table->addColumn('save_shipping_address', 'boolean', []);
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
        $table->addUniqueIndex(['billing_address_id'], 'uniq_e56b559d79d0c0e4');
        $table->addUniqueIndex(['source_id'], 'uniq_e56b559d953c1c61');
        $table->addUniqueIndex(['workflow_item_id'], 'uniq_e56b559d1023c4ee');
        $table->addUniqueIndex(['shipping_address_id'], 'uniq_e56b559d4d4cff2b');
        $table->addUniqueIndex(['order_id'], 'uniq_e56b559d8d9f6d38');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add orob2b_checkout foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BCheckoutForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_checkout');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_order'),
            ['order_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_step'),
            ['workflow_step_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_item'),
            ['workflow_item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
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
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_order_address'),
            ['shipping_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_order_address'),
            ['billing_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }
}
