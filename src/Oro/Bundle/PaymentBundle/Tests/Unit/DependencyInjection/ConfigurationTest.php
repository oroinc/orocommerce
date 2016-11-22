<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;

use Oro\Bundle\PaymentBundle\DependencyInjection\Configuration;

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
                            'value' => 'US',
                            'scope' => 'app'
                        ],
                    ]
                ]
            ]
        ];
    }
}
