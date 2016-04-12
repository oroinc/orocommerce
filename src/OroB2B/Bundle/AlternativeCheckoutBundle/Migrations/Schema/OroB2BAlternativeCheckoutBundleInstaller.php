<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroB2BAlternativeCheckoutBundleInstaller implements Installation
{
    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v1_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroB2BAlternativeCheckoutTable($schema);

        /** Foreign keys generation **/
        $this->addOroB2BAlternativeCheckoutForeignKeys($schema);
    }

    /**
     * Create orob2b_alternative_checkout table
     *
     * @param Schema $schema
     */
    protected function createOroB2BAlternativeCheckoutTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_alternative_checkout');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('workflow_step_id', 'integer', ['notnull' => false]);
        $table->addColumn('workflow_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('source_id', 'integer', []);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('billing_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('shipping_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('serialized_data', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->addColumn('save_billing_address', 'boolean', []);
        $table->addColumn('ship_to_billing_address', 'boolean', []);
        $table->addColumn('save_shipping_address', 'boolean', []);
        $table->addColumn('po_number', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('customer_notes', 'text', ['notnull' => false]);
        $table->addColumn('request_approval_notes', 'text', ['notnull' => false]);
        $table->addColumn('requested_for_approve', 'boolean', []);
        $table->addColumn('ship_until', 'date', ['notnull' => false, 'comment' => '(DC2Type:date)']);
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 3]);
        $table->addColumn('shipping_estimate_amount', 'money', [
            'notnull' => false,
            'precision' => 19,
            'scale' => 4,
            'comment' => '(DC2Type:money)'
        ]);
        $table->addColumn('shipping_estimate_currency', 'string', ['notnull' => false, 'length' => 3]);
        $table->addColumn('payment_method', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('updated_at', 'datetime', ['comment' => '(DC2Type:datetime)']);
        $table->addColumn('allowed', 'boolean', []);
        $table->addColumn('allow_request_date', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addIndex(['organization_id'], 'idx_organization_id', []);
        $table->addUniqueIndex(['workflow_item_id'], 'uniq_workflow_item_id');
        $table->addUniqueIndex(['billing_address_id'], 'uniq_billing_address_id');
        $table->addUniqueIndex(['shipping_address_id'], 'uniq_shipping_address_id');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['source_id'], 'uniq_source_id');
        $table->addIndex(['user_owner_id'], 'idx_user_owner_id', []);
        $table->addIndex(['workflow_step_id'], 'idx_workflow_step_id', []);
        $table->addIndex(['account_user_id'], 'idx_account_user_id', []);
        $table->addIndex(['account_id'], 'idx_account_id', []);
        $table->addIndex(['website_id'], 'idx_website_id', []);
    }

    /**
     * Add orob2b_alternative_checkout foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroB2BAlternativeCheckoutForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_alternative_checkout');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_step'),
            ['workflow_step_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL'],
            'fk_oro_workflow_step'
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_workflow_item'),
            ['workflow_item_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL'],
            'fk_oro_workflow_item'
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_checkout_source'),
            ['source_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => null],
            'fk_orob2b_checkout_source'
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL'],
            'fk_orob2b_website'
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
            ['account_user_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL'],
            'fk_orob2b_account_user'
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account'),
            ['account_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL'],
            'fk_orob2b_account'
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL'],
            'fk_oro_organization'
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL'],
            'fk_oro_user'
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_order_address'),
            ['billing_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL'],
            'fk_orob2b_order_address_billing'
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_order_address'),
            ['shipping_address_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL'],
            'fk_orob2b_order_address_shipping'
        );
    }
}
