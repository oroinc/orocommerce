<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;

use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\MappingConfiguration;

class MappingConfigurationTest extends \PHPUnit_Framework_TestCase
{
    public function testGetConfigTreeBuilder()
    {
        $configuration = new MappingConfiguration();
        $this->assertInstanceOf(
            'Symfony\Component\Config\Definition\Builder\TreeBuilder',
            $configuration->getConfigTreeBuilder()
        );
    }

    /**
     * @dataProvider mappingConfigurationDataProvider
     * @param array $configs
     * @param array $expected
     */
    public function testProcessConfiguration(array $configs, array $expected)
    {
        $configuration = new MappingConfiguration();
        $processor     = new Processor();

        $this->assertEquals($expected, $processor->processConfiguration($configuration, $configs));
    }

    public function mappingConfigurationDataProvider()
    {
        return [
            [
                'configs' => [
                    [
                        'mappings' => [
                            'Oro\Page' => [
                                'alias' => 'alias1',
                                'fields' => []
                            ]
                        ]
                    ]
                ],
                'expected' => [
                    'mappings' => [
                        'Oro\Page' => [
                            'alias' => 'alias1',
                            'fields' => []
                        ]
                    ]
                ],
            ]
        ];
    }
}
