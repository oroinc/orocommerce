<?php

namespace Oro\Bundle\InfinitePayBundle\Tests\Unit\Action\DependencyInjection;

use Oro\Bundle\InfinitePayBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

/**
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class ConfigurationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test Configuration.
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
     *
     * @param array $configs
     * @param array $expected
     */
    public function testProcessConfiguration(array $configs, array $expected)
    {
        $configuration = new Configuration();
        $processor = new Processor();

        $this->assertEquals($expected, $processor->processConfiguration($configuration, $configs));
    }

    /**
     * @return array
     */
    public function processConfigurationDataProvider()
    {
        return [
            'empty' => [
                'configs' => [[]],
                'expected' => ['settings' => [
                            'resolved' => true,
                            'infinite_pay_enabled' => [
                                    'value' => false,
                                    'scope' => 'app',
                                ],
                            'infinite_pay_sort_order' => [
                                    'value' => 30,
                                    'scope' => 'app',
                                ],
                            'infinite_pay_label' => [
                                    'value' => 'Infinite Pay (Invoice)',
                                    'scope' => 'app',
                                ],
                            'infinite_pay_label_short' => [
                                    'value' => 'Infinite Pay (Invoice)',
                                    'scope' => 'app',
                                ],
                            'infinite_pay_auto_capture' => [
                                    'value' => false,
                                    'scope' => 'app',
                                ],
                            'infinite_pay_auto_activate' => [
                                    'value' => false,
                                    'scope' => 'app',
                                ],
                            'infinite_pay_debug_mode' => [
                                    'value' => false,
                                    'scope' => 'app',
                                ],
                            'infinite_pay_client_ref' => [
                                    'value' => '',
                                    'scope' => 'app',
                                ],
                            'infinite_pay_username_token' => [
                                    'value' => '',
                                    'scope' => 'app',
                                ],
                            'infinite_pay_username' => [
                                    'value' => '',
                                    'scope' => 'app',
                                ],
                            'infinite_pay_password' => [
                                    'value' => '',
                                    'scope' => 'app',
                                ],
                            'infinite_pay_secret' => [
                                    'value' => '',
                                    'scope' => 'app',
                                ],
                            'invoice_due_period' => [
                                    'value' => 30,
                                    'scope' => 'app',
                                ],
                            'invoice_shipping_duration' => [
                                    'value' => 21,
                                    'scope' => 'app',
                                ],
                        ],

                    ],
            ],
        ];
    }
}
