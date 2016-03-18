<?php

namespace OroB2B\Bundle\PaymentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();

        $rootNode = $treeBuilder->root(OroB2BPaymentExtension::ALIAS);

        SettingsBuilder::append(
            $rootNode,
            [
                'merchant_country' => [
                    'type' => 'text',
                    'value' => ''
                ],

                // PayPal Payments Pro
                'paypal_payments_pro_enabled' => [
                    'type' => 'boolean',
                    'value' => false
                ],
                'paypal_payments_pro_label' => [
                    'type' => 'text',
                    'value' => 'Credit Card'
                ],
                'paypal_payments_pro_sort_order' => [
                    'type' => 'text',
                    'value' => ''
                ],
                'paypal_payments_pro_allowed_countries' => [
                    'type' => 'text',
                    'value' => 'all'
                ],
                'paypal_payments_pro_selected_countries' => [
                    'type' => 'array',
                    'value' => []
                ],
                'paypal_payments_pro_allowed_cc_types' => [
                    'type' => 'array',
                    'value' => ['visa', 'mastercard']
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
                'paypal_payments_pro_payment_action' => [
                    'type' => 'text',
                    'value' => 'authorization'
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
                    'value' => ''
                ],
                'paypal_payments_pro_debug_mode' => [
                    'type' => 'boolean',
                    'value' => false
                ],
                'paypal_payments_pro_enable_ssl_verification' => [
                    'type' => 'boolean',
                    'value' => true
                ],
                'paypal_payments_pro_require_cvv' => [
                    'type' => 'boolean',
                    'value' => true
                ],
                'paypal_payments_pro_validate_cvv' => [
                    'type' => 'boolean',
                    'value' => true
                ],

                // Payflow Gateway
                'payflow_gateway_enabled' => [
                    'type' => 'boolean',
                    'value' => false
                ],
                'payflow_gateway_label' => [
                    'type' => 'text',
                    'value' => 'Credit Card'
                ],
                'payflow_gateway_sort_order' => [
                    'type' => 'text',
                    'value' => ''
                ],
                'payflow_gateway_allowed_countries' => [
                    'type' => 'text',
                    'value' => 'all'
                ],
                'payflow_gateway_selected_countries' => [
                    'type' => 'array',
                    'value' => []
                ],
                'payflow_gateway_allowed_cc_types' => [
                    'type' => 'array',
                    'value' => ['visa', 'mastercard']
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
                'payflow_gateway_payment_action' => [
                    'type' => 'text',
                    'value' => 'authorization'
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
                    'value' => ''
                ],
                'payflow_gateway_debug_mode' => [
                    'type' => 'boolean',
                    'value' => false
                ],
                'payflow_gateway_enable_ssl_verification' => [
                    'type' => 'boolean',
                    'value' => true
                ],
                'payflow_gateway_require_cvv' => [
                    'type' => 'boolean',
                    'value' => true
                ],
                'payflow_gateway_validate_cvv' => [
                    'type' => 'boolean',
                    'value' => true
                ]
            ]
        );

        return $treeBuilder;
    }
}
