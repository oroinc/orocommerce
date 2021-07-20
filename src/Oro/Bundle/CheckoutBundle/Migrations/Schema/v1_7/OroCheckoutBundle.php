<?php

namespace Oro\Bundle\CheckoutBundle\Migrations\Schema\v1_7;

use Doctrine\DBAL\Schema\Schema;
use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroCheckoutBundle implements Migration
{
    /**
     * {@inheritDoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        /** Tables generation **/
        $this->createOroCheckoutLineItemTable($schema);
        $this->createOroCheckoutSubtotalTable($schema);

        /** Foreign keys generation **/
        $this->addOroCheckoutLineItemForeignKeys($schema);
        $this->addOroCheckoutSubtotalForeignKeys($schema);

        /** Create CheckoutLineItems for existing Checkouts **/
        $queries->addPostQuery(
            new ConvertCheckoutLineItemsDataQuery()
        );
    }

    /**
     * Create oro_checkout_line_item table
     */
    protected function createOroCheckoutLineItemTable(Schema $schema)
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
        $table->addColumn('price_type', 'integer', []);
        $table->addColumn('from_external_source', 'boolean', []);
        $table->addColumn('comment', 'text', ['notnull' => false]);
        $table->addColumn('is_price_fixed', 'boolean', []);
        $table->setPrimaryKey(['id']);
    }

    /**
     * Create oro_checkout_subtotal table
     */
    protected function createOroCheckoutSubtotalTable(Schema $schema)
    {
        $table = $schema->createTable('oro_checkout_subtotal');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('checkout_id', 'integer', []);
        $table->addColumn('currency', 'string', ['length' => 255]);
        $table->addColumn(
            'value',
            'money',
            ['notnull' => false, 'precision' => 19, 'scale' => 4, 'comment' => '(DC2Type:money)']
        );
        $table->addColumn('is_valid', 'boolean', []);
        $table->addUniqueIndex(['checkout_id', 'currency'], 'unique_checkout_currency');
        $table->setPrimaryKey(['id']);
    }

    /**
     * Add oro_checkout_line_item foreign keys.
     */
    protected function addOroCheckoutLineItemForeignKeys(Schema $schema)
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
    protected function addOroCheckoutSubtotalForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_checkout_subtotal');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_checkout'),
            ['checkout_id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
        );
    }
}
