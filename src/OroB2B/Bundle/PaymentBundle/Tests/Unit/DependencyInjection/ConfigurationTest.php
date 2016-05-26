<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;

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
                        'merchant_country' => [
                            'value' => '',
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_enabled' => [
                            'value' => false,
                            'scope' => 'app'
                        ],
                        'paypal_payments_pro_label' => [
                            'value' => Configuration::CREDIT_CARD_LABEL,
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
                                Configuration::CARD_VISA,
                                Configuration::CARD_MASTERCARD
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
                        'paypal_payments_pro_validate_cvv' => [
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

                        'payflow_gateway_enabled' => [
                            'value' => false,
                            'scope' => 'app'
                        ],
                        'payflow_gateway_label' => [
                            'value' => Configuration::CREDIT_CARD_LABEL,
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
                                Configuration::CARD_VISA,
                                Configuration::CARD_MASTERCARD
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
                        'payflow_gateway_validate_cvv' => [
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
                        'payment_term_enabled' => [
                            'value' => true,
                            'scope' => 'app'
                        ],
                        'payment_term_label' => [
                            'value' => Configuration::PAYMENT_TERM_LABEL,
                            'scope' => 'app'
                        ],

                        'payment_term_sort_order' => [
                            'value' => '30',
                            'scope' => 'app'
                        ],
                        'payment_term_allowed_countries' => [
                            'value' => 'all',
                            'scope' => 'app'
                        ],
                        'payment_term_selected_countries' => [
                            'value' => [],
                            'scope' => 'app'
                        ]
                    ]
                ]
            ]
        ];
    }
}
