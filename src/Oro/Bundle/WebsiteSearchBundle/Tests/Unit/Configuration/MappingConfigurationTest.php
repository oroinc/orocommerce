<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Configuration;

use Oro\Bundle\WebsiteSearchBundle\Configuration\MappingConfiguration;
use Symfony\Component\Config\Definition\Processor;

class MappingConfigurationTest extends \PHPUnit\Framework\TestCase
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

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
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
                            'type' => 'text',
                            'organization_id' => 3
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
                            'store' => false,
                            'fulltext' => false,
                        ]
                    ]
                ]
            ]
        ];

        $expected = [
            'Oro\Page' => [
                'alias' => 'PageSecondAlias',
                'synonyms_enabled' => false,
                'fields' => [
                    'pageFirstField' => [
                        'name' => 'pageFirstField',
                        'type' => 'text',
                        'default_search_field' => true,
                        'fulltext' => true,
                        'organization_id' => null,
                        'store' => true,
                        'group' => 'main'
                    ],
                    'pageSecondField' => [
                        'name' => 'pageSecondField',
                        'type' => 'integer',
                        'fulltext' => false,
                        'organization_id' => null,
                        'store' => true,
                        'group' => 'main'
                    ]
                ]
            ],
            'Oro\Product' => [
                'alias' => 'ProductThirdAlias',
                'synonyms_enabled' => false,
                'fields' => [
                    'productFirstField' => [
                        'name' => 'productFirstField',
                        'type' => 'text',
                        'fulltext' => true,
                        'organization_id' => 3,
                        'store' => true,
                        'group' => 'main'
                    ],
                    'productSecondField' => [
                        'name' => 'productSecondField',
                        'type' => 'decimal',
                        'fulltext' => false,
                        'organization_id' => null,
                        'store' => true,
                        'group' => 'main'
                    ],
                    'productThirdField' => [
                        'name'  => 'productThirdField',
                        'type'  => 'text',
                        'store' => false,
                        'fulltext' => false,
                        'organization_id' => null,
                        'group' => 'main'
                    ]
                ]
            ]
        ];

        $this->assertEquals($expected, $this->processConfiguration($configs));
    }
}
