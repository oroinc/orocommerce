<?php

namespace OroB2B\Bundle\InvoiceBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class OroB2BInvoiceBundleInstaller implements Installation
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
        $this->createOrob2BInvoiceTable($schema);
        $this->createOrob2BInvoiceLineItemTable($schema);

        /** Foreign keys generation **/
        $this->addOrob2BInvoiceForeignKeys($schema);
        $this->addOrob2BInvoiceLineItemForeignKeys($schema);
    }

    /**
     * Create orob2b_invoice table
     *
     * @param Schema $schema
     */
    protected function createOrob2BInvoiceTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_invoice');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', []);
        $table->addColumn('invoice_number', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('invoice_date', 'date', []);
        $table->addColumn('po_number', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('created_at', 'datetime', []);
        $table->addColumn('updated_at', 'datetime', []);
        $table->addColumn('serialized_data', 'array', ['notnull' => false, 'comment' => '(DC2Type:array)']);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['invoice_number'], 'UNIQ_1CB885202DA68207');
        $table->addIndex(['user_owner_id'], 'IDX_1CB885209EB185F9', []);
        $table->addIndex(['created_at'], 'orob2b_invoice_created_at_index', []);
        $table->addIndex(['organization_id'], 'IDX_1CB8852032C8A3DE', []);
        $table->addIndex(['account_id'], 'IDX_1CB885209B6B5FBA', []);
        $table->addIndex(['account_user_id'], 'IDX_1CB885206E45C7DD', []);
    }

    /**
     * Create orob2b_invoice_line_item table
     *
     * @param Schema $schema
     */
    protected function createOrob2BInvoiceLineItemTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_invoice_line_item');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('product_unit_id', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('invoice_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_id', 'integer', ['notnull' => false]);
        $table->addColumn('product_sku', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('free_form_product', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('quantity', 'float', ['notnull' => false]);
        $table->addColumn('product_unit_code', 'string', ['notnull' => false, 'length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['product_id'], 'IDX_1F380AA24584665A', []);
        $table->addIndex(['product_unit_id'], 'IDX_1F380AA229646BBD', []);
        $table->addIndex(['invoice_id'], 'IDX_1F380AA22989F1FD', []);
    }

    /**
     * Add orob2b_invoice foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BInvoiceForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_invoice');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_organization'),
            ['organization_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_account_user'),
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
            $schema->getTable('orob2b_account'),
            ['account_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add orob2b_invoice_line_item foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOrob2BInvoiceLineItemForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('orob2b_invoice_line_item');
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product_unit'),
            ['product_unit_id'],
            ['code'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_invoice'),
            ['invoice_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('orob2b_product'),
            ['product_id'],
            ['id'],
            ['onDelete' => 'SET NULL', 'onUpdate' => null]
        );
    }
}
