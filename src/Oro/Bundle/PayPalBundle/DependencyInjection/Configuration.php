<?php

namespace Oro\Bundle\PayPalBundle\DependencyInjection;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    const PAYPAL_PAYMENTS_PRO_LABEL_KEY = 'paypal_payments_pro_label';
    const PAYPAL_PAYMENTS_PRO_SHORT_LABEL_KEY = 'paypal_payments_pro_short_label';
    const PAYPAL_PAYMENTS_PRO_ALLOWED_CC_TYPES_KEY = 'paypal_payments_pro_allowed_cc_types';
    const PAYPAL_PAYMENTS_PRO_PARTNER_KEY = 'paypal_payments_pro_partner';
    const PAYPAL_PAYMENTS_PRO_USER_KEY = 'paypal_payments_pro_user';
    const PAYPAL_PAYMENTS_PRO_VENDOR_KEY = 'paypal_payments_pro_vendor';
    const PAYPAL_PAYMENTS_PRO_PASSWORD_KEY = 'paypal_payments_pro_password';
    const PAYPAL_PAYMENTS_PRO_PAYMENT_ACTION_KEY = 'paypal_payments_pro_payment_action';
    const PAYPAL_PAYMENTS_PRO_TEST_MODE_KEY = 'paypal_payments_pro_test_mode';
    const PAYPAL_PAYMENTS_PRO_USE_PROXY_KEY = 'paypal_payments_pro_use_proxy';
    const PAYPAL_PAYMENTS_PRO_PROXY_HOST_KEY = 'paypal_payments_pro_proxy_host';
    const PAYPAL_PAYMENTS_PRO_PROXY_PORT_KEY = 'paypal_payments_pro_proxy_port';
    const PAYPAL_PAYMENTS_PRO_DEBUG_MODE_KEY = 'paypal_payments_pro_debug_mode';
    const PAYPAL_PAYMENTS_PRO_ENABLE_SSL_VERIFICATION_KEY = 'paypal_payments_pro_enable_ssl_verification';
    const PAYPAL_PAYMENTS_PRO_REQUIRE_CVV_KEY = 'paypal_payments_pro_require_cvv';
    const PAYPAL_PAYMENTS_PRO_ZERO_AMOUNT_AUTHORIZATION_KEY = 'paypal_payments_pro_zero_amount_authorization';
    const PAYPAL_PAYMENTS_PRO_AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY =
        'paypal_payments_pro_authorization_for_required_amount';

    const PAYPAL_PAYMENTS_PRO_EXPRESS_CHECKOUT_LABEL_KEY = 'paypal_payments_pro_express_checkout_label';
    const PAYPAL_PAYMENTS_PRO_EXPRESS_CHECKOUT_SHORT_LABEL_KEY = 'paypal_payments_pro_express_checkout_short_label';
    const PAYPAL_PAYMENTS_PRO_EXPRESS_CHECKOUT_PAYMENT_ACTION_KEY =
        'paypal_payments_pro_express_checkout_payment_action';

    const PAYFLOW_GATEWAY_LABEL_KEY = 'payflow_gateway_label';
    const PAYFLOW_GATEWAY_SHORT_LABEL_KEY = 'payflow_gateway_short_label';
    const PAYFLOW_GATEWAY_ALLOWED_CC_TYPES_KEY = 'payflow_gateway_allowed_cc_types';
    const PAYFLOW_GATEWAY_PARTNER_KEY = 'payflow_gateway_partner';
    const PAYFLOW_GATEWAY_USER_KEY = 'payflow_gateway_user';
    const PAYFLOW_GATEWAY_VENDOR_KEY = 'payflow_gateway_vendor';
    const PAYFLOW_GATEWAY_PASSWORD_KEY = 'payflow_gateway_password';
    const PAYFLOW_GATEWAY_PAYMENT_ACTION_KEY = 'payflow_gateway_payment_action';
    const PAYFLOW_GATEWAY_TEST_MODE_KEY = 'payflow_gateway_test_mode';
    const PAYFLOW_GATEWAY_USE_PROXY_KEY = 'payflow_gateway_use_proxy';
    const PAYFLOW_GATEWAY_PROXY_HOST_KEY = 'payflow_gateway_proxy_host';
    const PAYFLOW_GATEWAY_PROXY_PORT_KEY = 'payflow_gateway_proxy_port';
    const PAYFLOW_GATEWAY_DEBUG_MODE_KEY = 'payflow_gateway_debug_mode';
    const PAYFLOW_GATEWAY_ENABLE_SSL_VERIFICATION_KEY = 'payflow_gateway_enable_ssl_verification';
    const PAYFLOW_GATEWAY_REQUIRE_CVV_KEY = 'payflow_gateway_require_cvv';
    const PAYFLOW_GATEWAY_ZERO_AMOUNT_AUTHORIZATION_KEY = 'payflow_gateway_zero_amount_authorization';
    const PAYFLOW_GATEWAY_AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY = 'payflow_gateway_authorization_for_required_amount';

    const PAYFLOW_EXPRESS_CHECKOUT_LABEL_KEY = 'payflow_express_checkout_label';
    const PAYFLOW_EXPRESS_CHECKOUT_SHORT_LABEL_KEY = 'payflow_express_checkout_short_label';
    const PAYFLOW_EXPRESS_CHECKOUT_PAYMENT_ACTION_KEY = 'payflow_express_checkout_payment_action';

    const CARD_VISA = 'visa';
    const CARD_MASTERCARD = 'mastercard';
    const CARD_DISCOVER = 'discover';
    const CARD_AMERICAN_EXPRESS = 'american_express';

    const PAY_WITH_PAYPAL = 'Pay with PayPal';
    const PAYPAL = 'PayPal';
    const CREDIT_CARD_LABEL = 'Credit Card';

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root(OroPayPalExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                self::PAYPAL_PAYMENTS_PRO_LABEL_KEY => [
                    'type' => 'text',
                    'value' => self::CREDIT_CARD_LABEL
                ],
                self::PAYPAL_PAYMENTS_PRO_SHORT_LABEL_KEY => [
                    'type' => 'text',
                    'value' => self::CREDIT_CARD_LABEL
                ],
                self::PAYPAL_PAYMENTS_PRO_ALLOWED_CC_TYPES_KEY => [
                    'type' => 'array',
                    'value' => [self::CARD_VISA, self::CARD_MASTERCARD]
                ],
                self::PAYPAL_PAYMENTS_PRO_PARTNER_KEY => [
                    'type' => 'text',
                    'value' => ''
                ],
                self::PAYPAL_PAYMENTS_PRO_USER_KEY => [
                    'type' => 'text',
                    'value' => ''
                ],
                self::PAYPAL_PAYMENTS_PRO_VENDOR_KEY => [
                    'type' => 'text',
                    'value' => ''
                ],
                self::PAYPAL_PAYMENTS_PRO_PASSWORD_KEY => [
                    'type' => 'text',
                    'value' => ''
                ],
                self::PAYPAL_PAYMENTS_PRO_PAYMENT_ACTION_KEY => [
                    'type' => 'text',
                    'value' => PaymentMethodInterface::AUTHORIZE
                ],
                self::PAYPAL_PAYMENTS_PRO_TEST_MODE_KEY => [
                    'type' => 'boolean',
                    'value' => false
                ],
                self::PAYPAL_PAYMENTS_PRO_USE_PROXY_KEY => [
                    'type' => 'boolean',
                    'value' => false
                ],
                self::PAYPAL_PAYMENTS_PRO_PROXY_HOST_KEY => [
                    'type' => 'text',
                    'value' => ''
                ],
                self::PAYPAL_PAYMENTS_PRO_PROXY_PORT_KEY => [
                    'type' => 'string',
                    'value' => ''
                ],
                self::PAYPAL_PAYMENTS_PRO_DEBUG_MODE_KEY => [
                    'type' => 'boolean',
                    'value' => false
                ],
                self::PAYPAL_PAYMENTS_PRO_ENABLE_SSL_VERIFICATION_KEY => [
                    'type' => 'boolean',
                    'value' => true
                ],
                self::PAYPAL_PAYMENTS_PRO_REQUIRE_CVV_KEY => [
                    'type' => 'boolean',
                    'value' => true
                ],
                self::PAYPAL_PAYMENTS_PRO_ZERO_AMOUNT_AUTHORIZATION_KEY => [
                    'type' => 'boolean',
                    'value' => false
                ],
                self::PAYPAL_PAYMENTS_PRO_AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY => [
                    'type' => 'boolean',
                    'value' => false
                ],
                self::PAYPAL_PAYMENTS_PRO_EXPRESS_CHECKOUT_LABEL_KEY => [
                    'type' => 'text',
                    'value' => self::PAY_WITH_PAYPAL
                ],
                self::PAYPAL_PAYMENTS_PRO_EXPRESS_CHECKOUT_SHORT_LABEL_KEY => [
                    'type' => 'text',
                    'value' => self::PAYPAL
                ],
                self::PAYPAL_PAYMENTS_PRO_EXPRESS_CHECKOUT_PAYMENT_ACTION_KEY => [
                    'type' => 'text',
                    'value' => PaymentMethodInterface::AUTHORIZE
                ],
                // Payflow Gateway
                self::PAYFLOW_GATEWAY_LABEL_KEY => [
                    'type' => 'text',
                    'value' => self::CREDIT_CARD_LABEL
                ],
                self::PAYFLOW_GATEWAY_SHORT_LABEL_KEY => [
                    'type' => 'text',
                    'value' => self::CREDIT_CARD_LABEL
                ],
                self::PAYFLOW_GATEWAY_ALLOWED_CC_TYPES_KEY => [
                    'type' => 'array',
                    'value' => [self::CARD_VISA, self::CARD_MASTERCARD]
                ],
                self::PAYFLOW_GATEWAY_PARTNER_KEY => [
                    'type' => 'text',
                    'value' => ''
                ],
                self::PAYFLOW_GATEWAY_USER_KEY => [
                    'type' => 'text',
                    'value' => ''
                ],
                self::PAYFLOW_GATEWAY_VENDOR_KEY => [
                    'type' => 'text',
                    'value' => ''
                ],
                self::PAYFLOW_GATEWAY_PASSWORD_KEY => [
                    'type' => 'text',
                    'value' => ''
                ],
                self::PAYFLOW_GATEWAY_PAYMENT_ACTION_KEY => [
                    'type' => 'text',
                    'value' => PaymentMethodInterface::AUTHORIZE
                ],
                self::PAYFLOW_GATEWAY_TEST_MODE_KEY => [
                    'type' => 'boolean',
                    'value' => false
                ],
                self::PAYFLOW_GATEWAY_USE_PROXY_KEY => [
                    'type' => 'boolean',
                    'value' => false
                ],
                self::PAYFLOW_GATEWAY_PROXY_HOST_KEY => [
                    'type' => 'text',
                    'value' => ''
                ],
                self::PAYFLOW_GATEWAY_PROXY_PORT_KEY => [
                    'type' => 'string',
                    'value' => ''
                ],
                self::PAYFLOW_GATEWAY_DEBUG_MODE_KEY => [
                    'type' => 'boolean',
                    'value' => false
                ],
                self::PAYFLOW_GATEWAY_ENABLE_SSL_VERIFICATION_KEY => [
                    'type' => 'boolean',
                    'value' => true
                ],
                self::PAYFLOW_GATEWAY_REQUIRE_CVV_KEY => [
                    'type' => 'boolean',
                    'value' => true
                ],
                self::PAYFLOW_GATEWAY_ZERO_AMOUNT_AUTHORIZATION_KEY => [
                    'type' => 'boolean',
                    'value' => false
                ],
                self::PAYFLOW_GATEWAY_AUTHORIZATION_FOR_REQUIRED_AMOUNT_KEY => [
                    'type' => 'boolean',
                    'value' => false
                ],
                self::PAYFLOW_EXPRESS_CHECKOUT_LABEL_KEY => [
                    'type' => 'text',
                    'value' => self::PAY_WITH_PAYPAL
                ],
                self::PAYFLOW_EXPRESS_CHECKOUT_SHORT_LABEL_KEY => [
                    'type' => 'text',
                    'value' => self::PAYPAL
                ],
                self::PAYFLOW_EXPRESS_CHECKOUT_PAYMENT_ACTION_KEY => [
                    'type' => 'text',
                    'value' => PaymentMethodInterface::AUTHORIZE
                ],
            ]
        );

        return $treeBuilder;
    }

    /**
     * @param string $key
     * @return string
     */
    public static function getFullConfigKey($key)
    {
        return OroPayPalExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . $key;
    }
}
