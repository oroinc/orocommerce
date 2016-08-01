<?php

namespace Oro\Bundle\PayPalBundle\Migrations\Schema\v1_0;

use Doctrine\DBAL\Schema\Schema;

use Oro\Bundle\MigrationBundle\Migration\Migration;
use Oro\Bundle\MigrationBundle\Migration\ParametrizedSqlMigrationQuery;
use Oro\Bundle\MigrationBundle\Migration\QueryBag;
use Oro\Bundle\PayPalBundle\DependencyInjection\OroPayPalExtension;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\OroB2BPaymentExtension;

class MigratePayPalSettings implements Migration
{
    /**
     * {@inheritdoc}
     */
    public function up(Schema $schema, QueryBag $queries)
    {
        // PayPal Payments Pro
        $this->migrateSetting($queries, 'paypal_payments_pro_enabled');
        $this->migrateSetting($queries, 'paypal_payments_pro_label');
        $this->migrateSetting($queries, 'paypal_payments_pro_short_label');
        $this->migrateSetting($queries, 'paypal_payments_pro_sort_order');
        $this->migrateSetting($queries, 'paypal_payments_pro_allowed_countries');
        $this->migrateSetting($queries, 'paypal_payments_pro_selected_countries');
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
        $this->migrateSetting($queries, 'payflow_gateway_enabled');
        $this->migrateSetting($queries, 'payflow_gateway_label');
        $this->migrateSetting($queries, 'payflow_gateway_short_label');
        $this->migrateSetting($queries, 'payflow_gateway_sort_order');
        $this->migrateSetting($queries, 'payflow_gateway_allowed_countries');
        $this->migrateSetting($queries, 'payflow_gateway_selected_countries');
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
        $this->migrateSetting($queries, 'payflow_express_checkout_enabled');
        $this->migrateSetting($queries, 'payflow_express_checkout_label');
        $this->migrateSetting($queries, 'payflow_express_checkout_short_label');
        $this->migrateSetting($queries, 'payflow_express_checkout_sort_order');
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
}
