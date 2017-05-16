<?php

namespace Oro\Bundle\ProductBundle\Tests\Service;

use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\ProductBundle\Service\ProductCollectionDefinitionConverter;

class ProductCollectionDefinitionConverterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ProductCollectionDefinitionConverter
     */
    private $definitionConverter;

    /**
     * {@inheritDoc}
     */
    public function setUp()
    {
        $this->definitionConverter = new ProductCollectionDefinitionConverter();
    }

    /**
     * @dataProvider putDefinitionsDataProvider
     *
     * @param array $definition
     * @param string $includedIds
     * @param string $excludedIds
     * @param array $expectedDefinition
     */
    public function testPutDefinitionParts(
        array $definition,
        $includedIds,
        $excludedIds,
        array $expectedDefinition
    ) {
        $resultDefinition = $this->definitionConverter->putConditionsInDefinition(
            json_encode($definition),
            $excludedIds,
            $includedIds
        );

        $this->assertEquals($expectedDefinition, json_decode($resultDefinition, true));
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function putDefinitionsDataProvider()
    {
        return [
            'empty definition' => [
                'definition' => [],
                'includedIds' => '1,3,5',
                'excludedIds' => '7,9',
                'expectedDefinition' => [
                    'filters' => [
                        [
                            'alias' => ProductCollectionDefinitionConverter::INCLUDED_FILTER_ALIAS,
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => [
                                    'value' => '1,3,5',
                                    'type' => NumberFilterType::TYPE_IN
                                ]
                            ]
                        ],
                        'AND',
                        [
                            'alias' => ProductCollectionDefinitionConverter::EXCLUDED_FILTER_ALIAS,
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => [
                                    'value' => '7,9',
                                    'type' => NumberFilterType::TYPE_NOT_IN
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'definition with filters' => [
                'definition' => [
                    'columns' => [
                        [
                            'name' => "sku",
                            'label' => "sku",
                            'sorting' => 'DESC',
                            'func' => null
                        ]
                    ],
                    'filters' => [
                        'columnName' => 'id',
                        'criterion' => [
                            'filter' => 'number',
                            'data' => [
                                'value' => 10,
                                'type' => NumberFilterType::TYPE_LESS_THAN
                            ]
                        ]
                    ]
                ],
                'includedIds' => '1,3,5',
                'excludedIds' => '7,9',
                'expectedDefinition' => [
                    'columns' => [
                        [
                            'name' => "sku",
                            'label' => "sku",
                            'sorting' => 'DESC',
                            'func' => null
                        ]
                    ],
                    'filters' => [
                        [
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => [
                                    'value' => 10,
                                    'type' => NumberFilterType::TYPE_LESS_THAN
                                ]
                            ]
                        ],
                        'OR',
                        [
                            'alias' => ProductCollectionDefinitionConverter::INCLUDED_FILTER_ALIAS,
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => [
                                    'value' => '1,3,5',
                                    'type' => NumberFilterType::TYPE_IN
                                ]
                            ]
                        ],
                        'AND',
                        [
                            'alias' => ProductCollectionDefinitionConverter::EXCLUDED_FILTER_ALIAS,
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => [
                                    'value' => '7,9',
                                    'type' => NumberFilterType::TYPE_NOT_IN
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'definition already contains included/excluded filters' => [
                'definition' => [
                    'columns' => [
                        [
                            'name' => "id",
                            'label' => "id",
                            'sorting' => 'DESC',
                            'func' => null
                        ]
                    ],
                    'filters' => [
                        'columnName' => 'id',
                        'criterion' => [
                            'filter' => 'number',
                            'data' => [
                                'value' => 8,
                                'type' => NumberFilterType::TYPE_LESS_THAN
                            ]
                        ],
                        'OR',
                        [
                            'alias' => ProductCollectionDefinitionConverter::INCLUDED_FILTER_ALIAS,
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => [
                                    'value' => '6,7',
                                    'type' => NumberFilterType::TYPE_IN
                                ]
                            ]
                        ],
                        'AND',
                        [
                            'alias' => ProductCollectionDefinitionConverter::EXCLUDED_FILTER_ALIAS,
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => [
                                    'value' => '8,9',
                                    'type' => NumberFilterType::TYPE_NOT_IN
                                ]
                            ]
                        ]
                    ]
                ],
                'includedIds' => '600,700',
                'excludedIds' => '800,900',
                'expectedDefinition' => [
                    'columns' => [
                        [
                            'name' => "id",
                            'label' => "id",
                            'sorting' => 'DESC',
                            'func' => null
                        ]
                    ],
                    'filters' => [
                        [
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => [
                                    'value' => 8,
                                    'type' => NumberFilterType::TYPE_LESS_THAN
                                ]
                            ]
                        ],
                        'OR',
                        [
                            'alias' => ProductCollectionDefinitionConverter::INCLUDED_FILTER_ALIAS,
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => [
                                    'value' => '600,700',
                                    'type' => NumberFilterType::TYPE_IN
                                ]
                            ]
                        ],
                        'AND',
                        [
                            'alias' => ProductCollectionDefinitionConverter::EXCLUDED_FILTER_ALIAS,
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => [
                                    'value' => '800,900',
                                    'type' => NumberFilterType::TYPE_NOT_IN
                                ]
                            ]
                        ]
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider notNormalizedDefinitionDataProvider
     * @param string $definition
     */
    public function testPutConditionsInDefinitionWithNotNormalizedDefinition($definition)
    {
        $includedIds = '1,3,5';
        $excludedIds = '7,9';
        $expectedDefinition = [
            'filters' => [
                [
                    'alias' => ProductCollectionDefinitionConverter::INCLUDED_FILTER_ALIAS,
                    'columnName' => 'id',
                    'criterion' => [
                        'filter' => 'number',
                        'data' => [
                            'value' => '1,3,5',
                            'type' => NumberFilterType::TYPE_IN
                        ]
                    ]
                ],
                'AND',
                [
                    'alias' => ProductCollectionDefinitionConverter::EXCLUDED_FILTER_ALIAS,
                    'columnName' => 'id',
                    'criterion' => [
                        'filter' => 'number',
                        'data' => [
                            'value' => '7,9',
                            'type' => NumberFilterType::TYPE_NOT_IN
                        ]
                    ]
                ]
            ]
        ];

        $resultDefinition = $this->definitionConverter->putConditionsInDefinition(
            $definition,
            $excludedIds,
            $includedIds
        );

        $this->assertEquals($expectedDefinition, json_decode($resultDefinition, true));
    }

    /**
     * @return array
     */
    public function notNormalizedDefinitionDataProvider()
    {
        return [
            'empty string' => [
                'definition' => ''
            ],
            'null' => [
                'definition' => ''
            ],
            'json string' => [
                'definition' => json_encode('some string')
            ],
            'not valid json' => [
                'definition' => '{{x'
            ]
        ];
    }

    /**
     * @dataProvider notNormalizedDefinitionDataProvider
     * @param string $definition
     */
    public function testGetDefinitionPartsWithNotNormalizedDefinition($definition)
    {
        $expectedParts = [
            'definition' => '[]',
            'included' => null,
            'excluded' => null
        ];

        $this->assertEquals($expectedParts, $this->definitionConverter->getDefinitionParts($definition));
    }

    /**
     * @dataProvider getDefinitionsDataProvider
     *
     * @param array $definition
     * @param array $expectedDefinition
     * @param string $expectedIncluded
     * @param string $expectedExcluded
     */
    public function testGetDefinitionParts(
        array $definition,
        array $expectedDefinition,
        $expectedIncluded,
        $expectedExcluded
    ) {
        $definitionParts = $this->definitionConverter->getDefinitionParts(json_encode($definition));

        $this->assertEquals(
            $expectedDefinition,
            json_decode($definitionParts['definition'], true)
        );
        $this->assertEquals(
            $expectedIncluded,
            $definitionParts['included']
        );
        $this->assertEquals(
            $expectedExcluded,
            $definitionParts['excluded']
        );
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getDefinitionsDataProvider()
    {
        $definitionWithoutIncludedExcludedFilters = [
            'columns' => [
                [
                    'name' => "sku",
                    'label' => "sku",
                    'sorting' => 'DESC',
                    'func' => null
                ]
            ],
            'filters' => [
                [
                    'columnName' => 'id',
                    'criterion' => [
                        'filter' => 'number',
                        'data' => [
                            'value' => 10,
                            'type' => NumberFilterType::TYPE_LESS_THAN
                        ]
                    ]
                ]
            ]
        ];

        return [
            'empty segment' => [
                'definition' => [],
                'expectedDefinition' => [],
                'expectedIncluded' => null,
                'expectedExcluded' => null
            ],
            'segment without filters' => [
                'definition' => [
                    'columns' => [
                        [
                            'name' => "sku",
                            'label' => "sku",
                            'sorting' => 'DESC',
                            'func' => null
                        ]
                    ],
                ],
                'expectedDefinition' => [
                    'columns' => [
                        [
                            'name' => "sku",
                            'label' => "sku",
                            'sorting' => 'DESC',
                            'func' => null
                        ]
                    ],
                ],
                'expectedIncluded' => null,
                'expectedExcluded' => null
            ],
            'segment with a filter but without included/excluded filters' => [
                'definition' => $definitionWithoutIncludedExcludedFilters,
                'expectedDefinition' => $definitionWithoutIncludedExcludedFilters,
                'expectedIncluded' => null,
                'expectedExcluded' => null
            ],
            'segment with only included/excluded filters' => [
                'definition' => [
                    'columns' => [
                        [
                            'name' => "sku",
                            'label' => "sku",
                            'sorting' => 'DESC',
                            'func' => null
                        ]
                    ],
                    'filters' => [
                        [
                            'alias' => ProductCollectionDefinitionConverter::INCLUDED_FILTER_ALIAS,
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => [
                                    'value' => '1,20',
                                    'type' => NumberFilterType::TYPE_IN
                                ]
                            ]
                        ],
                        'AND',
                        [
                            'alias' => ProductCollectionDefinitionConverter::EXCLUDED_FILTER_ALIAS,
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => [
                                    'value' => '3,7,8',
                                    'type' => NumberFilterType::TYPE_NOT_IN
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedDefinition' => [
                    'columns' => [
                        [
                            'name' => "sku",
                            'label' => "sku",
                            'sorting' => 'DESC',
                            'func' => null
                        ]
                    ],
                    'filters' => []
                ],
                'expectedIncluded' => '1,20',
                'expectedExcluded' => '3,7,8'
            ],
            'segment with one additional filter' => [
                'definition' => [
                    'columns' => [
                        [
                            'name' => "sku",
                            'label' => "sku",
                            'sorting' => 'DESC',
                            'func' => null
                        ]
                    ],
                    'filters' => [
                        [
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => [
                                    'value' => 10,
                                    'type' => NumberFilterType::TYPE_LESS_THAN
                                ]
                            ]
                        ],
                        'OR',
                        [
                            'alias' => ProductCollectionDefinitionConverter::INCLUDED_FILTER_ALIAS,
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => [
                                    'value' => '1,2',
                                    'type' => NumberFilterType::TYPE_IN
                                ]
                            ]
                        ],
                        'AND',
                        [
                            'alias' => ProductCollectionDefinitionConverter::EXCLUDED_FILTER_ALIAS,
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => [
                                    'value' => '3',
                                    'type' => NumberFilterType::TYPE_NOT_IN
                                ]
                            ]
                        ]
                    ]
                ],
                'expectedDefinition' => [
                    'columns' => [
                        [
                            'name' => "sku",
                            'label' => "sku",
                            'sorting' => 'DESC',
                            'func' => null
                        ],
                    ],
                    'filters' => [
                        'columnName' => 'id',
                        'criterion' => [
                            'filter' => 'number',
                            'data' => [
                                'value' => 10,
                                'type' => NumberFilterType::TYPE_LESS_THAN
                            ]
                        ]
                    ]
                ],
                'expectedIncluded' => '1,2',
                'expectedExcluded' => '3'
            ],
        ];
    }
}
