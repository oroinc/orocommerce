<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BAlternativeCheckoutBundleInstaller implements Installation
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

        $table->addColumn('id', 'integer', ['notnull' => true]);
        $table->addColumn('billing_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('shipping_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('save_billing_address', 'boolean', []);
        $table->addColumn('ship_to_billing_address', 'boolean', []);
        $table->addColumn('save_shipping_address', 'boolean', []);
        $table->addColumn('allowed', 'boolean', []);
        $table->addColumn('allow_request_date', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('request_approval_notes', 'text', ['notnull' => false]);
        $table->addColumn('requested_for_approve', 'boolean', []);

        $table->setPrimaryKey(['id']);

        $table->addUniqueIndex(['billing_address_id'], 'uniq_alt_checkout_bill_addr');
        $table->addUniqueIndex(['shipping_address_id'], 'uniq_alt_checkout_shipp_addr');
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
            $schema->getTable('orob2b_checkout'),
            ['id'],
            ['id'],
            ['onUpdate' => null, 'onDelete' => 'CASCADE']
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
