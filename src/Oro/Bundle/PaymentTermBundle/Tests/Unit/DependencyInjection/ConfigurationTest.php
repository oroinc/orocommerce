<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\PaymentTermBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Processor;

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
                        'payment_term_label' => [
                            'value' => 'Payment Terms',
                            'scope' => 'app'
                        ],
                        'payment_term_short_label' => [
                            'value' => 'Payment Terms',
                            'scope' => 'app'
                        ],
                    ]
                ]
            ]
        ];
    }
}
