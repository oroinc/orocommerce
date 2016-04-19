<?php

namespace OroB2B\Bundle\AlternatiiveCheckoutBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BAlternativeCheckoutBundle implements Migration, OrderedMigrationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 10;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->renameOldAlternativeCheckoutTable($schema);
        $this->createOroB2BAlternativeCheckoutTable($schema);
        $this->addOroB2BAlternativeCheckoutForeignKeys($schema);
        $this->moveExistingAlternativeCheckoutsToBaseTable($queries);
        $this->moveExistingAlternativeCheckoutsToAdditionalTable($queries);
    }

    protected function renameOldAlternativeCheckoutTable(Schema $schema)
    {
        $schema->renameTable('orob2b_alternative_checkout', 'orob2b_alternative_checkout_old');
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

        $table->addUniqueIndex(['billing_address_id'], 'uniq_default_checkout_billing_address');
        $table->addUniqueIndex(['shipping_address_id'], 'uniq_default_checkout_shipping_address');
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

    /**
     * @param QueryBag $queries
     */
    protected function moveExistingAlternativeCheckoutsToBaseTable(QueryBag $queries)
    {
        $sql = <<<SQL
    INSERT INTO orob2b_checkout (workflow_step_id,
        workflow_item_id,
        source_id,
        website_id,
        account_user_id,
        account_id,
        organization_id,
        user_owner_id,
        po_number,
        customer_notes,
        currency,
        ship_until,
        created_at,
        updated_at,
        shipping_estimate_amount,
        shipping_estimate_currency,
        payment_method,
        type)
    SELECT workflow_step_id,
        workflow_item_id,
        source_id,
        website_id,
        account_user_id,
        account_id,
        organization_id,
        user_owner_id,
        po_number,
        customer_notes,
        currency,
        ship_until,
        created_at,
        updated_at,
        shipping_estimate_amount,
        shipping_estimate_currency,
        payment_method,
        'alternativecheckout'
     FROM orob2b_alternative_checkout_old
SQL;
        $queries->addQuery($sql);
    }

    /**
     * @param QueryBag $queries
     */
    protected function moveExistingAlternativeCheckoutsToAdditionalTable(QueryBag $queries)
    {
        $sql = <<<SQL
    INSERT INTO orob2b_alternative_checkout (id,
        shipping_address_id,
        billing_address_id,
        save_billing_address,
        ship_to_billing_address,
        save_shipping_address,
        allowed,
        allow_request_date,
        request_approval_notes,
        requested_for_approve)
    SELECT c.id,
        shipping_address_id,
        billing_address_id,
        save_billing_address,
        ship_to_billing_address,
        save_shipping_address,
        aco.allowed,
        aco.allow_request_date,
        aco.request_approval_notes,
        aco.requested_for_approve
    FROM orob2b_alternative_checkout_old o2b aco
    LEFT join orob2b_checkout c ON c.workflow_item_id = ac.workflow_item_id
SQL;
        $queries->addQuery($sql);
    }
}
