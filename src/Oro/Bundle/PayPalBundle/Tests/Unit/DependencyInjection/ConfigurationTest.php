<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;

use Oro\Bundle\PayPalBundle\DependencyInjection\Configuration;
use Oro\Bundle\CurrencyBundle\DependencyInjection\Configuration as CurrencyConfiguraton;

/**
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test Configuration
     */
    public function testGetConfigTreeBuilder()
    {
        $configuration = new Configuration();
        $this->assertInstanceOf(
            'Symfony\Component\Config\Definition\Builder\TreeBuilder',
            $configuration->getConfigTreeBuilder()
        );
    }

    /**
     * @dataProvider processConfigurationDataProvider
     * @param array $configs
     * @param array $expected
     */
    public function testProcessConfiguration(array $configs, array $expected)
    {
        $configuration = new Configuration();
        $processor     = new Processor();

        $this->assertEquals($expected, $processor->processConfiguration($configuration, $configs));
    }

    /**
     * @return array
     */
    public function processConfigurationDataProvider()
    {
        return [
            'empty' => [
                'configs'  => [[]],
                'expected' => [
                    'settings' => [
                        'resolved' => true,
                        'paypal_payments_pro_enabled' => [
                            'value' => false,
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_label' => [
                            'value' => 'Credit Card',
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_short_label' => [
                            'value' => 'Credit Card',
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_sort_order' => [
                            'value' => '10',
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_allowed_countries' => [
                            'value' => 'all',
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_selected_countries' => [
                            'value' => [],
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_allowed_cc_types' => [
                            'value' => [
                                'visa',
                                'mastercard'
                            ],
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_partner' => [
                            'value' => '',
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_user' => [
                            'value' => '',
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_vendor' => [
                            'value' => '',
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_password' => [
                            'value' => '',
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_payment_action' => [
                            'value' => 'authorize',
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_test_mode' => [
                            'value' => false,
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_use_proxy' => [
                            'value' => false,
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_proxy_host' => [
                            'value' => '',
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_proxy_port' => [
                            'value' => '',
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_debug_mode' => [
                            'value' => false,
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_enable_ssl_verification' => [
                            'value' => true,
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_require_cvv' => [
                            'value' => true,
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_zero_amount_authorization' => [
                            'value' => false,
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_authorization_for_required_amount' => [
                            'value' => false,
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_allowed_currencies' => [
                            'value' =>  CurrencyConfiguraton::$defaultCurrencies,
                            'scope' => 'app'
                        ],
                        'payflow_gateway_enabled' => [
                            'value' => false,
                            'scope' => 'app'
                        ],
                        'payflow_gateway_label' => [
                            'value' => 'Credit Card',
                            'scope' => 'app'
                        ],
                        'payflow_gateway_short_label' => [
                            'value' => 'Credit Card',
                            'scope' => 'app'
                        ],
                        'payflow_gateway_sort_order' => [
                            'value' => '20',
                            'scope' => 'app'
                        ],
                        'payflow_gateway_allowed_countries' => [
                            'value' => 'all',
                            'scope' => 'app'
                        ],
                        'payflow_gateway_selected_countries' => [
                            'value' => [],
                            'scope' => 'app'
                        ],
                        'payflow_gateway_allowed_cc_types' => [
                            'value' => [
                                'visa',
                                'mastercard'
                            ],
                            'scope' => 'app'
                        ],
                        'payflow_gateway_partner' => [
                            'value' => '',
                            'scope' => 'app'
                        ],
                        'payflow_gateway_user' => [
                            'value' => '',
                            'scope' => 'app'
                        ],
                        'payflow_gateway_vendor' => [
                            'value' => '',
                            'scope' => 'app'
                        ],
                        'payflow_gateway_password' => [
                            'value' => '',
                            'scope' => 'app'
                        ],
                        'payflow_gateway_payment_action' => [
                            'value' => 'authorize',
                            'scope' => 'app'
                        ],
                        'payflow_gateway_test_mode' => [
                            'value' => false,
                            'scope' => 'app'
                        ],
                        'payflow_gateway_use_proxy' => [
                            'value' => false,
                            'scope' => 'app'
                        ],
                        'payflow_gateway_proxy_host' => [
                            'value' => '',
                            'scope' => 'app'
                        ],
                        'payflow_gateway_proxy_port' => [
                            'value' => '',
                            'scope' => 'app'
                        ],
                        'payflow_gateway_debug_mode' => [
                            'value' => false,
                            'scope' => 'app'
                        ],
                        'payflow_gateway_enable_ssl_verification' => [
                            'value' => true,
                            'scope' => 'app'
                        ],
                        'payflow_gateway_require_cvv' => [
                            'value' => true,
                            'scope' => 'app'
                        ],
                        'payflow_gateway_zero_amount_authorization' => [
                            'value' => false,
                            'scope' => 'app'
                        ],
                        'payflow_gateway_authorization_for_required_amount' => [
                            'value' => false,
                            'scope' => 'app'
                        ],
                        'payflow_gateway_allowed_currencies' => [
                            'value' => CurrencyConfiguraton::$defaultCurrencies,
                            'scope' => 'app'
                        ],
                        'payflow_express_checkout_enabled' => [
                            'value' => false,
                            'scope' => 'app'
                        ],
                        'payflow_express_checkout_label' => [
                            'value' => 'Pay with PayPal',
                            'scope' => 'app'
                        ],
                        'payflow_express_checkout_short_label' => [
                            'value' => 'PayPal',
                            'scope' => 'app'
                        ],
                        'payflow_express_checkout_sort_order' => [
                            'value' => '40',
                            'scope' => 'app'
                        ],
                        'payflow_express_checkout_payment_action' => [
                            'value' => 'authorize',
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_express_checkout_enabled' => [
                            'value' => false,
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_express_checkout_label' => [
                            'value' => 'Pay with PayPal',
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_express_checkout_short_label' => [
                            'value' => 'PayPal',
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_express_checkout_sort_order' => [
                            'value' => '30',
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_express_checkout_payment_action' => [
                            'value' => 'authorize',
                            'scope' => 'app'
                        ]
                    ]
                ]
            ]
        ];
    }
}
