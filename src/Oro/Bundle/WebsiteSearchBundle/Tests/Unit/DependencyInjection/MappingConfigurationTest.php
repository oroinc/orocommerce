<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\MappingConfiguration;
use Symfony\Component\Config\Definition\Processor;

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
                            'type' => 'text',
                            'default_search_field' => true
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
            ],
            [
                'Oro\Product' => [
                    'alias' => 'ProductThirdAlias',
                    'fields' => [
                        [
                            'name'  => 'productThirdField',
                            'type'  => 'text',
                            'store' => false
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
                        'type' => 'text',
                        'default_search_field' => true
                    ],
                    'pageSecondField' => [
                        'name' => 'pageSecondField',
                        'type' => 'integer'
                    ]
                ]
            ],
            'Oro\Product' => [
                'alias' => 'ProductThirdAlias',
                'fields' => [
                    'productFirstField' => [
                        'name' => 'productFirstField',
                        'type' => 'text'
                    ],
                    'productSecondField' => [
                        'name' => 'productSecondField',
                        'type' => 'decimal'
                    ],
                    'productThirdField' => [
                        'name'  => 'productThirdField',
                        'type'  => 'text',
                        'store' => false
                    ]
                ]
            ]
        ];

        $this->assertEquals($expected, $this->processConfiguration($configs));
    }
}
