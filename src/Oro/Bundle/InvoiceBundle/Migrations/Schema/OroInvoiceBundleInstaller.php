<?php

namespace Oro\Bundle\InvoiceBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroInvoiceBundleInstaller implements Installation
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
        $this->createOroInvoiceTable($schema);
        $this->createOroInvoiceLineItemTable($schema);

        /** Foreign keys generation **/
        $this->addOroInvoiceForeignKeys($schema);
        $this->addOroInvoiceLineItemForeignKeys($schema);
    }

    /**
     * Create oro_invoice table
     *
     * @param Schema $schema
     */
    protected function createOroInvoiceTable(Schema $schema)
    {
        $table = $schema->createTable('oro_invoice');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('invoice_number', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('invoice_date', 'date', []);
        $table->addColumn('po_number', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('currency', 'string', ['notnull' => false, 'length' => 3]);
        $table->addColumn(
            'subtotal',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn('payment_due_date', 'date');
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['invoice_number'], 'UNIQ_1CB885202DA68207');
        $table->addIndex(['created_at'], 'oro_invoice_created_at_index', []);
    }

    /**
     * Create oro_invoice_line_item table
     *
     * @param Schema $schema
     */
    protected function createOroInvoiceLineItemTable(Schema $schema)
    {
        $table = $schema->createTable('oro_invoice_line_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_unit_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('invoice_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_sku', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('free_form_product', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('quantity', 'float', ['notnull' => false]);
        $table->addColumn('product_unit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('sort_order', 'integer', []);
        $table->addColumn(
            'price_value',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn('price_currency', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('price_type', 'integer', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_invoice foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroInvoiceForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_invoice');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account_user'),
            ['account_user_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_user'),
            ['user_owner_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_account'),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_website'),
            ['website_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'SET NULL']
        );
    }

    /**
     * Add oro_invoice_line_item foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroInvoiceLineItemForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_invoice_line_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product_unit'),
            ['product_unit_id'],
            ['code'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_invoice'),
            ['invoice_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
