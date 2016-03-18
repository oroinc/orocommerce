<?php

namespace OroB2B\Bundle\PaymentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    const PAYFLOW_GATEWAY_ENABLED_KEY = 'payflow_gateway_enabled';
    const PAYFLOW_GATEWAY_EMAIL_KEY = 'payflow_gateway_email';
    const PAYFLOW_GATEWAY_PARTNER_KEY = 'payflow_gateway_partner';
    const PAYFLOW_GATEWAY_USER_KEY = 'payflow_gateway_user';
    const PAYFLOW_GATEWAY_VENDOR_KEY = 'payflow_gateway_vendor';
    const PAYFLOW_GATEWAY_PASSWORD_KEY = 'payflow_gateway_password';
    const PAYFLOW_GATEWAY_TEST_MODE_KEY = 'payflow_gateway_test_mode';
    const PAYFLOW_GATEWAY_USE_PROXY_KEY = 'payflow_gateway_use_proxy';
    const PAYFLOW_GATEWAY_PROXY_HOST_KEY = 'payflow_gateway_proxy_host';
    const PAYFLOW_GATEWAY_PROXY_PORT_KEY = 'payflow_gateway_proxy_port';
    const PAYPAL_PAYMENTS_PRO_ENABLED_KEY = 'paypal_payments_pro_enabled';
    const PAYPAL_PAYMENTS_PRO_EMAIL_KEY = 'paypal_payments_pro_email';
    const PAYPAL_PAYMENTS_PRO_PARTNER_KEY = 'paypal_payments_pro_partner';
    const PAYPAL_PAYMENTS_PRO_USER_KEY = 'paypal_payments_pro_user';
    const PAYPAL_PAYMENTS_PRO_VENDOR_KEY = 'paypal_payments_pro_vendor';
    const PAYPAL_PAYMENTS_PRO_PASSWORD_KEY = 'paypal_payments_pro_password';
    const PAYPAL_PAYMENTS_PRO_TEST_MODE_KEY = 'paypal_payments_pro_test_mode';
    const PAYPAL_PAYMENTS_PRO_USE_PROXY_KEY = 'paypal_payments_pro_use_proxy';
    const PAYPAL_PAYMENTS_PRO_PROXY_HOST_KEY = 'paypal_payments_pro_proxy_host';
    const PAYPAL_PAYMENTS_PRO_PROXY_PORT_KEY = 'paypal_payments_pro_proxy_port';

    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root(OroB2BPaymentExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                // Payflow Gateway
                'payflow_gateway_enabled' => [
                    'type' => 'boolean',
                    'value' => false
                ],
                'payflow_gateway_email' => [
                    'type' => 'email',
                    'value' => ''
                ],
                'payflow_gateway_partner' => [
                    'type' => 'text',
                    'value' => ''
                ],
                'payflow_gateway_user' => [
                    'type' => 'text',
                    'value' => ''
                ],
                'payflow_gateway_vendor' => [
                    'type' => 'text',
                    'value' => ''
                ],
                'payflow_gateway_password' => [
                    'type' => 'text',
                    'value' => ''
                ],
                'payflow_gateway_test_mode' => [
                    'type' => 'boolean',
                    'value' => false
                ],
                'payflow_gateway_use_proxy' => [
                    'type' => 'boolean',
                    'value' => false
                ],
                'payflow_gateway_proxy_host' => [
                    'type' => 'text',
                    'value' => ''
                ],
                'payflow_gateway_proxy_port' => [
                    'type' => 'text',
                    'value' => 8080
                ],
                // PayPal Payments Pro
                'paypal_payments_pro_enabled' => [
                    'type' => 'boolean',
                    'value' => false
                ],
                'paypal_payments_pro_email' => [
                    'type' => 'email',
                    'value' => ''
                ],
                'paypal_payments_pro_partner' => [
                    'type' => 'text',
                    'value' => ''
                ],
                'paypal_payments_pro_user' => [
                    'type' => 'text',
                    'value' => ''
                ],
                'paypal_payments_pro_vendor' => [
                    'type' => 'text',
                    'value' => ''
                ],
                'paypal_payments_pro_password' => [
                    'type' => 'text',
                    'value' => ''
                ],
                'paypal_payments_pro_test_mode' => [
                    'type' => 'boolean',
                    'value' => false
                ],
                'paypal_payments_pro_use_proxy' => [
                    'type' => 'boolean',
                    'value' => false
                ],
                'paypal_payments_pro_proxy_host' => [
                    'type' => 'text',
                    'value' => ''
                ],
                'paypal_payments_pro_proxy_port' => [
                    'type' => 'text',
                    'value' => 8080
                ],
            ]
        );

        return $treeBuilder;
    }
}
