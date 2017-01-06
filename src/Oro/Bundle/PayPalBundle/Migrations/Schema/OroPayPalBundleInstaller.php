<?php

namespace Oro\Bundle\PayPalBundle\Migrations\Schema;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtension;
use Oro\Bundle\EntityExtendBundle\Migration\Extension\ExtendExtensionAwareInterface;
use Oro\Bundle\MigrationBundle\Migration\Installation;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;

class OroPayPalBundleInstaller implements Installation, ExtendExtensionAwareInterface
{
    /**
     * @var ExtendExtension
     */
    protected $extendExtension;

    /**
     * {@inheritdoc}
     */
    public function getMigrationVersion()
    {
        return 'v2_0';
    }

    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        $this->migratePayPalSettings($schema, $queries);
        $this->createOroPaypalCreditCardLblTable($schema);
        $this->createOroPaypalCreditCardShrtLblTable($schema);
        $this->createOroPaypalXprssChktLblTable($schema);
        $this->createOroPaypalXprssChktShrtLblTable($schema);
        $this->updateOroIntegrationTransportTable($schema);
        $this->addOroPaypalCreditCardLblForeignKeys($schema);
        $this->addOroPaypalCreditCardShrtLblForeignKeys($schema);
        $this->addOroPaypalXprssChktLblForeignKeys($schema);
        $this->addOroPaypalXprssChktShrtLblForeignKeys($schema);
    }

    /**
     * @param Schema $schema
     * @param QueryBag $queries
     */
    protected function migratePayPalSettings(Schema $schema, QueryBag $queries)
    {
        // PayPal Payments Pro
        $this->migrateSetting($queries, 'paypal_payments_pro_label');
        $this->migrateSetting($queries, 'paypal_payments_pro_short_label');
        $this->migrateSetting($queries, 'paypal_payments_pro_allowed_cc_types');
        $this->migrateSetting($queries, 'paypal_payments_pro_partner');
        $this->migrateSetting($queries, 'paypal_payments_pro_user');
        $this->migrateSetting($queries, 'paypal_payments_pro_vendor');
        $this->migrateSetting($queries, 'paypal_payments_pro_password');
        $this->migrateSetting($queries, 'paypal_payments_pro_payment_action');
        $this->migrateSetting($queries, 'paypal_payments_pro_test_mode');
        $this->migrateSetting($queries, 'paypal_payments_pro_use_proxy');
        $this->migrateSetting($queries, 'paypal_payments_pro_proxy_host');
        $this->migrateSetting($queries, 'paypal_payments_pro_proxy_port');
        $this->migrateSetting($queries, 'paypal_payments_pro_debug_mode');
        $this->migrateSetting($queries, 'paypal_payments_pro_enable_ssl_verification');
        $this->migrateSetting($queries, 'paypal_payments_pro_require_cvv');
        $this->migrateSetting($queries, 'paypal_payments_pro_zero_amount_authorization');
        $this->migrateSetting($queries, 'paypal_payments_pro_authorization_for_required_amount');
        $this->migrateSetting($queries, 'paypal_payments_pro_allowed_currencies');
        $this->migrateSetting($queries, 'paypal_payments_pro_express_checkout_enabled');
        $this->migrateSetting($queries, 'paypal_payments_pro_express_checkout_label');
        $this->migrateSetting($queries, 'paypal_payments_pro_express_checkout_short_label');
        $this->migrateSetting($queries, 'paypal_payments_pro_express_checkout_sort_order');
        $this->migrateSetting($queries, 'paypal_payments_pro_express_checkout_payment_action');

        // Payflow Gateway
        $this->migrateSetting($queries, 'payflow_gateway_label');
        $this->migrateSetting($queries, 'payflow_gateway_short_label');
        $this->migrateSetting($queries, 'payflow_gateway_allowed_cc_types');
        $this->migrateSetting($queries, 'payflow_gateway_partner');
        $this->migrateSetting($queries, 'payflow_gateway_user');
        $this->migrateSetting($queries, 'payflow_gateway_vendor');
        $this->migrateSetting($queries, 'payflow_gateway_password');
        $this->migrateSetting($queries, 'payflow_gateway_payment_action');
        $this->migrateSetting($queries, 'payflow_gateway_test_mode');
        $this->migrateSetting($queries, 'payflow_gateway_use_proxy');
        $this->migrateSetting($queries, 'payflow_gateway_proxy_host');
        $this->migrateSetting($queries, 'payflow_gateway_proxy_port');
        $this->migrateSetting($queries, 'payflow_gateway_debug_mode');
        $this->migrateSetting($queries, 'payflow_gateway_enable_ssl_verification');
        $this->migrateSetting($queries, 'payflow_gateway_require_cvv');
        $this->migrateSetting($queries, 'payflow_gateway_zero_amount_authorization');
        $this->migrateSetting($queries, 'payflow_gateway_authorization_for_required_amount');
        $this->migrateSetting($queries, 'payflow_gateway_allowed_currencies');

        // Payflow Express Checkout
        $this->migrateSetting($queries, 'payflow_express_checkout_label');
        $this->migrateSetting($queries, 'payflow_express_checkout_short_label');
        $this->migrateSetting($queries, 'payflow_express_checkout_payment_action');
    }

    /**
     * @param QueryBag $queries
     * @param string $name
     */
    protected function migrateSetting(QueryBag $queries, $name)
    {
        $queries->addQuery(new ParametrizedSqlMigrationQuery(
            'UPDATE oro_config_value SET section = :new_section WHERE name = :name AND section = :old_section',
            [
                'name' => $name,
                'new_section' => 'oro_paypal',
                'old_section' => 'orob2b_payment'
            ]
        ));
    }

    /**
     * @param Schema $schema
     */
    public function updateOroIntegrationTransportTable(Schema $schema)
    {
        $table = $schema->getTable('oro_integration_transport');
        $table->addColumn('pp_express_checkout_name', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('pp_partner', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('pp_vendor', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('pp_user', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('pp_password', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('pp_test_mode', 'boolean', ['default' => '0', 'notnull' => false]);
        $table->addColumn('pp_debug_mode', 'boolean', ['default' => '0', 'notnull' => false]);
        $table->addColumn('pp_require_cvv_entry', 'boolean', ['default' => '1', 'notnull' => false]);
        $table->addColumn('pp_zero_amount_authorization', 'boolean', ['default' => '0', 'notnull' => false]);
        $table->addColumn('pp_auth_for_req_amount', 'boolean', ['default' => '0', 'notnull' => false]);
        $table->addColumn('pp_use_proxy', 'boolean', ['default' => '0', 'notnull' => false]);
        $table->addColumn('pp_proxy_host', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('pp_proxy_port', 'string', ['notnull' => false, 'length' => 255]);
        $table->addColumn('pp_enable_ssl_verification', 'boolean', ['default' => '1', 'notnull' => false]);
//        $this->extendExtension->addEnumField(
//            $schema,
//            'oro_integration_transport',
//            'credit_card_types',
//            'pp_credit_card_types',
//            true
//        );
//        $this->extendExtension->addEnumField(
//            $schema,
//            'oro_integration_transport',
//            'credit_card_payment_action',
//            'pp_credit_card_payment_action'
//        );
//        $this->extendExtension->addEnumField(
//            $schema,
//            'oro_integration_transport',
//            'express_checkout_payment_action',
//            'pp_express_checkout_payment_action'
//        );
    }


    /**
     * Add oro_paypal_credit_card_lbl foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPaypalCreditCardLblForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_paypal_credit_card_lbl');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_paypal_credit_card_shrt_lbl foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPaypalCreditCardShrtLblForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_paypal_credit_card_shrt_lbl');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_paypal_xprss_chkt_lbl foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPaypalXprssChktLblForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_paypal_xprss_chkt_lbl');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Add oro_paypal_xprss_chkt_shrt_lbl foreign keys.
     *
     * @param Schema $schema
     */
    protected function addOroPaypalXprssChktShrtLblForeignKeys(Schema $schema)
    {
        $table = $schema->getTable('oro_paypal_xprss_chkt_shrt_lbl');
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_integration_transport'),
            ['transport_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
        $table->addForeignKeyConstraint(
            $schema->getTable('oro_fallback_localization_val'),
            ['localized_value_id'],
            ['id'],
            ['onDelete' => 'CASCADE', 'onUpdate' => null]
        );
    }

    /**
     * Create oro_paypal_credit_card_lbl table
     *
     * @param Schema $schema
     */
    protected function createOroPaypalCreditCardLblTable(Schema $schema)
    {
        $table = $schema->createTable('oro_paypal_credit_card_lbl');
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_92E5B87EEB576E89');
        $table->addIndex(['transport_id'], 'IDX_92E5B87E9909C13F', []);
    }

    /**
     * Create oro_paypal_credit_card_shrt_lbl table
     *
     * @param Schema $schema
     */
    protected function createOroPaypalCreditCardShrtLblTable(Schema $schema)
    {
        $table = $schema->createTable('oro_paypal_credit_card_shrt_lbl');
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_415165DEEB576E89');
        $table->addIndex(['transport_id'], 'IDX_415165DE9909C13F', []);
    }

    /**
     * Create oro_paypal_xprss_chkt_lbl table
     *
     * @param Schema $schema
     */
    protected function createOroPaypalXprssChktLblTable(Schema $schema)
    {
        $table = $schema->createTable('oro_paypal_xprss_chkt_lbl');
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_386D1FC6EB576E89');
        $table->addIndex(['transport_id'], 'IDX_386D1FC69909C13F', []);
    }

    /**
     * Create oro_paypal_xprss_chkt_shrt_lbl table
     *
     * @param Schema $schema
     */
    protected function createOroPaypalXprssChktShrtLblTable(Schema $schema)
    {
        $table = $schema->createTable('oro_paypal_xprss_chkt_shrt_lbl');
        $table->addColumn('transport_id', 'integer', []);
        $table->addColumn('localized_value_id', 'integer', []);
        $table->setPrimaryKey(['transport_id', 'localized_value_id']);
        $table->addUniqueIndex(['localized_value_id'], 'UNIQ_A9419ECEB576E89');
        $table->addIndex(['transport_id'], 'IDX_A9419EC9909C13F', []);
    }

    /**
     * {@inheritdoc}
     */
    public function setExtendExtension(ExtendExtension $extendExtension)
    {
        $this->extendExtension = $extendExtension;
    }
}
