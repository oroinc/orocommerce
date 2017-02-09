<?php

namespace Oro\Bundle\PayPalBundle\Migrations\Data\ORM\Config;

class PayPalConfigKeysProviderFactory
{
    /**
     * @return PayPalConfigKeysProvider
     */
    public static function createPaymentsProConfigKeyProvider()
    {
        return new PayPalConfigKeysProvider([
            PayPalConfigKeysProvider::LABEL_KEY => 'paypal_payments_pro_label',
            PayPalConfigKeysProvider::SHORT_LABEL_KEY => 'paypal_payments_pro_short_label',
            PayPalConfigKeysProvider::ALLOWED_CC_TYPES_KEY => 'paypal_payments_pro_allowed_cc_types',
            PayPalConfigKeysProvider::PARTNER_KEY => 'paypal_payments_pro_partner',
            PayPalConfigKeysProvider::USER_KEY => 'paypal_payments_pro_user',
            PayPalConfigKeysProvider::PASSWORD_KEY => 'paypal_payments_pro_password',
            PayPalConfigKeysProvider::VENDOR_KEY => 'paypal_payments_pro_vendor',
            PayPalConfigKeysProvider::PAYMENT_ACTION_KEY => 'paypal_payments_pro_payment_action',
            PayPalConfigKeysProvider::TEST_MODE_KEY => 'paypal_payments_pro_test_mode',
            PayPalConfigKeysProvider::USE_PROXY_KEY => 'paypal_payments_pro_use_proxy',
            PayPalConfigKeysProvider::PROXY_HOST_KEY => 'paypal_payments_pro_proxy_host',
            PayPalConfigKeysProvider::PROXY_PORT_KEY => 'paypal_payments_pro_proxy_port',
            PayPalConfigKeysProvider::DEBUG_MODE_KEY => 'paypal_payments_pro_debug_mode',
            PayPalConfigKeysProvider::ENABLE_SSL_VERIFICATION_KEY => 'paypal_payments_pro_enable_ssl_verification',
            PayPalConfigKeysProvider::REQUIRE_CVV_KEY => 'paypal_payments_pro_require_cvv',
            PayPalConfigKeysProvider::ZERO_AMOUNT_AUTHORIZATION_KEY => 'paypal_payments_pro_zero_amount_authorization',
            PayPalConfigKeysProvider::AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY =>
                'paypal_payments_pro_authorization_for_required_amount',

            PayPalConfigKeysProvider::EXPRESS_CHECKOUT_LABEL_KEY => 'paypal_payments_pro_express_checkout_label',
            PayPalConfigKeysProvider::EXPRESS_CHECKOUT_SHORT_LABEL_KEY =>
                'paypal_payments_pro_express_checkout_short_label',
            PayPalConfigKeysProvider::EXPRESS_CHECKOUT_PAYMENT_ACTION_KEY =>
                'paypal_payments_pro_express_checkout_payment_action',
        ]);
    }

    /**
     * @return PayPalConfigKeysProvider
     */
    public static function createPayflowGatewayConfigKeyProvider()
    {
        return new PayPalConfigKeysProvider([
            PayPalConfigKeysProvider::LABEL_KEY => 'payflow_gateway_label',
            PayPalConfigKeysProvider::SHORT_LABEL_KEY => 'payflow_gateway_short_label',
            PayPalConfigKeysProvider::ALLOWED_CC_TYPES_KEY => 'payflow_gateway_allowed_cc_types',
            PayPalConfigKeysProvider::PARTNER_KEY => 'payflow_gateway_partner',
            PayPalConfigKeysProvider::USER_KEY => 'payflow_gateway_user',
            PayPalConfigKeysProvider::PASSWORD_KEY => 'payflow_gateway_password',
            PayPalConfigKeysProvider::VENDOR_KEY => 'payflow_gateway_vendor',
            PayPalConfigKeysProvider::PAYMENT_ACTION_KEY => 'payflow_gateway_payment_action',
            PayPalConfigKeysProvider::TEST_MODE_KEY => 'payflow_gateway_test_mode',
            PayPalConfigKeysProvider::USE_PROXY_KEY => 'payflow_gateway_use_proxy',
            PayPalConfigKeysProvider::PROXY_HOST_KEY => 'payflow_gateway_proxy_host',
            PayPalConfigKeysProvider::PROXY_PORT_KEY => 'payflow_gateway_proxy_port',
            PayPalConfigKeysProvider::DEBUG_MODE_KEY => 'payflow_gateway_debug_mode',
            PayPalConfigKeysProvider::ENABLE_SSL_VERIFICATION_KEY => 'payflow_gateway_enable_ssl_verification',
            PayPalConfigKeysProvider::REQUIRE_CVV_KEY => 'payflow_gateway_require_cvv',
            PayPalConfigKeysProvider::ZERO_AMOUNT_AUTHORIZATION_KEY => 'payflow_gateway_zero_amount_authorization',
            PayPalConfigKeysProvider::AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY =>
                'payflow_gateway_authorization_for_required_amount',

            PayPalConfigKeysProvider::EXPRESS_CHECKOUT_LABEL_KEY => 'payflow_express_checkout_label',
            PayPalConfigKeysProvider::EXPRESS_CHECKOUT_SHORT_LABEL_KEY => 'payflow_express_checkout_short_label',
            PayPalConfigKeysProvider::EXPRESS_CHECKOUT_PAYMENT_ACTION_KEY => 'payflow_express_checkout_payment_action',
        ]);
    }
}
