<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;

use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\Configuration;

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
                        'name' => [
                            'value' => 'ORM',
                            'scope' => 'app'
                        ],
                        'host' => [
                            'value' => 'localhost',
                            'scope' => 'app'
                        ],
                        'port' => [
                            'value' => 9200,
                            'scope' => 'app'
                        ],
                        'username' => [
                            'value' => '',
                            'scope' => 'app'
                        ],
                        'password' => [
                            'value' => '',
                            'scope' => 'app'
                        ],
                        'auth_type' => [
                            'value' => 'basic',
                            'scope' => 'app'
                        ],
                    ]
                ]
            ]
        ];
    }
}
