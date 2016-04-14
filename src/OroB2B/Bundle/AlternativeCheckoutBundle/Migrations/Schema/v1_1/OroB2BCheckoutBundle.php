<?php

namespace OroB2B\Bundle\CheckoutBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroB2BAlternativeCheckoutBundle implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->addAlternativeCheckoutColumns($schema);
        $this->moveExistingAlternativeCheckouts($queries);
    }

    /**
     * Add checkout type column
     *
     * @param Schema $schema
     */
    protected function addAlternativeCheckoutColumns(Schema $schema)
    {
        $table = $schema->getTable('orob2b_checkout');
        $table->addColumn('allowed', 'boolean', []);
        $table->addColumn('allow_request_date', 'datetime', ['notnull' => false, 'comment' => '(DC2Type:datetime)']);
        $table->addColumn('request_approval_notes', 'text', ['notnull' => false]);
        $table->addColumn('requested_for_approve', 'boolean', []);
    }

    protected function moveExistingAlternativeCheckouts(QueryBag $queries)
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
        shipping_address_id,
        billing_address_id,
        save_billing_address,
        ship_to_billing_address,
        save_shipping_address,
        po_number,
        customer_notes,
        currency,
        ship_until,
        created_at,
        updated_at,
        shipping_estimate_amount,
        shipping_estimate_currency,
        payment_method,
        allowed,
        allow_request_date,
        request_approval_notes,
        requested_for_approve,
        type)
    SELECT workflow_step_id,
        workflow_item_id,
        source_id,
        website_id,
        account_user_id,
        account_id,
        organization_id,
        user_owner_id,
        shipping_address_id,
        billing_address_id,
        save_billing_address,
        ship_to_billing_address,
        save_shipping_address,
        po_number,
        customer_notes,
        currency,
        ship_until,
        created_at,
        updated_at,
        shipping_estimate_amount,
        shipping_estimate_currency,
        payment_method,
        allowed,
        allow_request_date,
        request_approval_notes,
        requested_for_approve,
        'alternativecheckout'
     FROM orob2b_alternative_checkout
SQL;
        $queries->addQuery($sql);
    }
}
