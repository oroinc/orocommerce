<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class CreateOldAlternativeCheckoutTable implements Migration, OrderedMigrationInterface
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
        $this->moveAlternativeCheckoutData($queries);
    }

    /**
     * Rename old alternative checkout table
     *
     * @param Schema $schema
     */
    protected function renameOldAlternativeCheckoutTable(Schema $schema)
    {
        $table = $schema->createTable('orob2b_alt_checkout_old');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('workflow_step_id', 'integer', ['notnull' => false]);
        $table->addColumn('workflow_item_id', 'integer', ['notnull' => false]);
        $table->addColumn('source_id', 'integer', []);
        $table->addColumn('website_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_user_id', 'integer', ['notnull' => false]);
        $table->addColumn('account_id', 'integer', ['notnull' => false]);
        $table->addColumn('organization_id', 'integer', ['notnull' => false]);
        $table->addColumn('user_owner_id', 'integer', ['notnull' => false]);
        $table->addColumn('billing_address_id', 'integer', ['notnull' => false]);
        $table->addColumn('shipping_address_id', 'integer', ['notnull' => false]);
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

        $table->setPrimaryKey(['id']);
    }

    /**
     * Move alternative checkout data
     *
     * @param QueryBag $queries
     */
    protected function moveAlternativeCheckoutData(QueryBag $queries)
    {
        $sql = <<<SQL
    INSERT INTO orob2b_alt_checkout_old
    SELECT *
    FROM orob2b_alternative_checkout
SQL;
        $queries->addQuery($sql);
    }
}
