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
     * @param array $configs
     * @return array
     */
    private function processConfiguration(array $configs)
    {
        $configuration = new MappingConfiguration();
        $processor = new Processor();

        return $processor->processConfiguration($configuration, $configs);
    }

    public function testDefaultFieldsValueIsEmptyArray()
    {
        $configs = [
            [
                'Oro\Page' => [
                    'alias' => 'PageAlias'
                ]
            ]
        ];

        $expected = [
            'Oro\Page' => [
                'alias' => 'PageAlias',
                'fields' => []
            ]
        ];

        $this->assertEquals($expected, $this->processConfiguration($configs));
    }

    public function testFieldsAreMerged()
    {
        $configs = [
            [
                'Oro\Page' => [
                    'alias' => 'PageFirstAlias',
                    'fields' => [
                        [
                            'name' => 'pageFirstField',
                            'type' => 'text'
                        ]
                    ]
                ]
            ],
            [
                'Oro\Page' => [
                    'alias' => 'PageSecondAlias',
                    'fields' => [
                        [
                            'name' => 'pageSecondField',
                            'type' => 'integer'
                        ]
                    ]
                ]
            ],
            [
                'Oro\Product' => [
                    'alias' => 'ProductFirstAlias',
                    'fields' => [
                        [
                            'name' => 'productFirstField',
                            'type' => 'text'
                        ]
                    ]
                ]
            ],
            [
                'Oro\Product' => [
                    'alias' => 'ProductSecondAlias',
                    'fields' => [
                        [
                            'name' => 'productSecondField',
                            'type' => 'decimal'
                        ]
                    ]
                ]
            ]
        ];

        $expected = [
            'Oro\Page' => [
                'alias' => 'PageSecondAlias',
                'fields' => [
                    'pageFirstField' => [
                        'name' => 'pageFirstField',
                        'type' => 'text'
                    ],
                    'pageSecondField' => [
                        'name' => 'pageSecondField',
                        'type' => 'integer'
                    ]
                ]
            ],
            'Oro\Product' => [
                'alias' => 'ProductSecondAlias',
                'fields' => [
                    'productFirstField' => [
                        'name' => 'productFirstField',
                        'type' => 'text'
                    ],
                    'productSecondField' => [
                        'name' => 'productSecondField',
                        'type' => 'decimal'
                    ],
                ]
            ]
        ];

        $this->assertEquals($expected, $this->processConfiguration($configs));
    }
}
