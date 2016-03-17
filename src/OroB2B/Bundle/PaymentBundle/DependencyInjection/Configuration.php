<?php

namespace OroB2B\Bundle\PaymentBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

use Oro\Bundle\ConfigBundle\DependencyInjection\SettingsBuilder;

class Configuration implements ConfigurationInterface
{
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
                    'type' => 'integer',
                    'value' => 8080
                ],
            ]
        );

        return $treeBuilder;
    }
}
