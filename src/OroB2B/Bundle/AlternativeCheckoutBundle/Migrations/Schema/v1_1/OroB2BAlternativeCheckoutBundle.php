<?php

namespace OroB2B\Bundle\AlternatiiveCheckoutBundle\Migrations\Schema\v1_1;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Type;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\OrderedMigrationInterface;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use OroB2B\Bundle\AlternativeCheckoutBundle\Entity\AlternativeCheckout;

class OroB2BAlternativeCheckoutBundle implements Migration, OrderedMigrationInterface
{
    const ALTERNATIVE_CHECKOUT_TYPE = 'alternativecheckout';

    /**
     * {@inheritdoc}
     */
    public function getOrder()
    {
        return 30;
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->createOroB2BAlternativeCheckoutTable($schema);
        $this->addOroB2BAlternativeCheckoutForeignKeys($schema);
        $this->moveExistingAlternativeCheckoutsToBaseTable($queries);
        $this->moveExistingAlternativeCheckoutsToAdditionalTable($queries);

        $queries->addPreQuery(
            new ParametrizedSqlMigrationQuery(
                'DELETE FROM oro_entity_config WHERE class_name = :class_name',
                [
                    'class_name'  => 'OroB2B\Bundle\AlternativeCheckoutBundle\Entity\AlternativeCheckout',
                ],
                [
                    'class_name'  => Type::STRING
                ]
            )
        );
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
        checkout_discriminator,
        checkout_type
    )
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
        '%s',
        '%s'
     FROM orob2b_alt_checkout_old
SQL;
        $queries->addQuery(sprintf($sql, self::ALTERNATIVE_CHECKOUT_TYPE, AlternativeCheckout::CHECKOUT_TYPE));
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
        aco.shipping_address_id,
        aco.billing_address_id,
        aco.save_billing_address,
        aco.ship_to_billing_address,
        aco.save_shipping_address,
        aco.allowed,
        aco.allow_request_date,
        aco.request_approval_notes,
        aco.requested_for_approve
    FROM orob2b_alt_checkout_old aco
    LEFT join orob2b_checkout c ON c.workflow_item_id = aco.workflow_item_id
SQL;
        $queries->addQuery($sql);
    }
}
