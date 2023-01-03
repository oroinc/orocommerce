<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Service;

use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\ProductBundle\Service\ProductCollectionDefinitionConverter;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\SegmentFiltersPurifier;

class ProductCollectionDefinitionConverterTest extends \PHPUnit\Framework\TestCase
{
    private const EXCLUDED_IDS = '1,111';
    private const INCLUDED_IDS = '2,7';

    /** @var SegmentFiltersPurifier|\PHPUnit\Framework\MockObject\MockObject */
    private $filtersPurifier;

    /** @var ProductCollectionDefinitionConverter */
    private $definitionConverter;

    protected function setUp(): void
    {
        $this->filtersPurifier = $this->createMock(SegmentFiltersPurifier::class);
        $this->definitionConverter = new ProductCollectionDefinitionConverter($this->filtersPurifier);
    }

    public function testPutDefinitionPartsWithPurifiedDefinition()
    {
        $this->filtersPurifier->expects($this->once())
            ->method('purifyFilters')
            ->willReturn([]);

        $resultDefinition = $this->definitionConverter->putConditionsInDefinition(
            json_encode([
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
            ]),
            self::EXCLUDED_IDS,
            self::INCLUDED_IDS
        );

        $expectedDefinition = [
            'filters' => [
                [
                    'alias' => ProductCollectionDefinitionConverter::INCLUDED_FILTER_ALIAS,
                    'columnName' => 'id',
                    'criterion' => [
                        'filter' => 'number',
                        'data' => [
                            'value' => self::INCLUDED_IDS,
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
                            'value' => self::EXCLUDED_IDS,
                            'type' => NumberFilterType::TYPE_NOT_IN
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedDefinition, json_decode($resultDefinition, true));
    }

    public function testPutDefinitionPartsWithEmptyDefinition()
    {
        $this->filtersPurifier->expects($this->never())
            ->method('purifyFilters');

        $resultDefinition = $this->definitionConverter->putConditionsInDefinition(
            json_encode([]),
            self::EXCLUDED_IDS,
            self::INCLUDED_IDS
        );

        $expectedDefinition = [
            'filters' => [
                [
                    'alias' => ProductCollectionDefinitionConverter::INCLUDED_FILTER_ALIAS,
                    'columnName' => 'id',
                    'criterion' => [
                        'filter' => 'number',
                        'data' => [
                            'value' => self::INCLUDED_IDS,
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
                            'value' => self::EXCLUDED_IDS,
                            'type' => NumberFilterType::TYPE_NOT_IN
                        ]
                    ]
                ]
            ]
        ];

        $this->assertEquals($expectedDefinition, json_decode($resultDefinition, true));
    }

    /**
     * @dataProvider putDefinitionsDataProvider
     */
    public function testPutDefinitionParts(
        array $definition,
        ?string $includedIds,
        ?string $excludedIds,
        array $expectedDefinition
    ) {
        $this->filtersPurifier->expects($this->once())
            ->method('purifyFilters')
            ->willReturnArgument(0);

        $resultDefinition = $this->definitionConverter->putConditionsInDefinition(
            json_encode($definition),
            $excludedIds,
            $includedIds
        );

        $this->assertEquals($expectedDefinition, json_decode($resultDefinition, true));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function putDefinitionsDataProvider(): array
    {
        return [
            'definition with filters' => [
                'definition' => [
                    'columns' => [
                        [
                            'name' => 'sku',
                            'label' => 'sku',
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
                ],
                'includedIds' => '1,3,5',
                'excludedIds' => '7,9',
                'expectedDefinition' => [
                    'columns' => [
                        [
                            'name' => 'sku',
                            'label' => 'sku',
                            'sorting' => 'DESC',
                            'func' => null
                        ]
                    ],
                    'filters' => [
                        [
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
            'definition with filters and no included/excluded ids' => [
                'definition' => [
                    'filters' => [
                        'columnName' => 'id',
                        'criterion' => [
                            'filter' => 'number',
                            'data' => [
                                'value' => 8,
                                'type' => NumberFilterType::TYPE_LESS_THAN
                            ]
                        ],
                    ]
                ],
                'includedIds' => null,
                'excludedIds' => null,
                'expectedDefinition' => [
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
                    ]
                ]
            ],
            'definition with filters and includedIds' => [
                'definition' => [
                    'filters' => [
                        'columnName' => 'id',
                        'criterion' => [
                            'filter' => 'number',
                            'data' => [
                                'value' => 8,
                                'type' => NumberFilterType::TYPE_LESS_THAN
                            ]
                        ],
                    ]
                ],
                'includedIds' => '1,7',
                'excludedIds' => null,
                'expectedDefinition' => [
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
                                    'value' => '1,7',
                                    'type' => NumberFilterType::TYPE_IN
                                ]
                            ]
                        ],
                    ]
                ]
            ],
            'definition with filters and excludedIds' => [
                'definition' => [
                    'filters' => [
                        'columnName' => 'id',
                        'criterion' => [
                            'filter' => 'number',
                            'data' => [
                                'value' => 8,
                                'type' => NumberFilterType::TYPE_LESS_THAN
                            ]
                        ],
                    ]
                ],
                'includedIds' => null,
                'excludedIds' => '7,1',
                'expectedDefinition' => [
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
                        'AND',
                        [
                            'alias' => ProductCollectionDefinitionConverter::EXCLUDED_FILTER_ALIAS,
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => [
                                    'value' => '7,1',
                                    'type' => NumberFilterType::TYPE_NOT_IN
                                ]
                            ]
                        ],
                    ]
                ]
            ],
        ];
    }

    /**
     * @dataProvider notNormalizedDefinitionDataProvider
     */
    public function testPutConditionsInDefinitionWithNotNormalizedDefinition(string $definition)
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

        $this->filtersPurifier->expects($this->never())
            ->method('purifyFilters');

        $resultDefinition = $this->definitionConverter->putConditionsInDefinition(
            $definition,
            $excludedIds,
            $includedIds
        );

        $this->assertEquals($expectedDefinition, json_decode($resultDefinition, true));
    }

    public function notNormalizedDefinitionDataProvider(): array
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
     */
    public function testGetDefinitionPartsWithNotNormalizedDefinition(string $definition)
    {
        $expectedParts = [
            'definition' => '[]',
            'included' => null,
            'excluded' => null
        ];

        $this->filtersPurifier->expects($this->never())
            ->method('purifyFilters');

        $this->assertEquals($expectedParts, $this->definitionConverter->getDefinitionParts($definition));
    }

    /**
     * @dataProvider getDefinitionsDataProvider
     */
    public function testGetDefinitionParts(
        array $definition,
        array $expectedDefinition,
        ?string $expectedIncluded,
        ?string $expectedExcluded
    ) {
        $this->filtersPurifier->expects($this->never())
            ->method('purifyFilters');

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
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getDefinitionsDataProvider(): array
    {
        $definitionWithoutIncludedExcludedFilters = [
            'filters' => [
                [
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
                            'name' => 'sku',
                            'label' => 'sku',
                            'sorting' => 'DESC',
                            'func' => null
                        ]
                    ],
                ],
                'expectedDefinition' => [
                    'columns' => [
                        [
                            'name' => 'sku',
                            'label' => 'sku',
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
                'expectedDefinition' => [
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
                ],
                'expectedIncluded' => null,
                'expectedExcluded' => null
            ],
            'segment with only included/excluded filters' => [
                'definition' => [
                    'columns' => [
                        [
                            'name' => 'sku',
                            'label' => 'sku',
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
                            'name' => 'sku',
                            'label' => 'sku',
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
                            'name' => 'sku',
                            'label' => 'sku',
                            'sorting' => 'DESC',
                            'func' => null
                        ]
                    ],
                    'filters' => [
                        [
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
                            'AND',
                            [
                                'columnName' => 'id',
                                'criterion' => [
                                    'filter' => 'number',
                                    'data' => [
                                        'value' => 1,
                                        'type' => NumberFilterType::TYPE_GREATER_THAN
                                    ]
                                ]
                            ],
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
                            'name' => 'sku',
                            'label' => 'sku',
                            'sorting' => 'DESC',
                            'func' => null
                        ],
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
                        'AND',
                        [
                            'columnName' => 'id',
                            'criterion' => [
                                'filter' => 'number',
                                'data' => [
                                    'value' => 1,
                                    'type' => NumberFilterType::TYPE_GREATER_THAN
                                ]
                            ]
                        ],
                    ]
                ],
                'expectedIncluded' => '1,2',
                'expectedExcluded' => '3'
            ],
        ];
    }

    /**
     * @dataProvider hasFiltersProvider
     */
    public function testHasFilters(mixed $definition, bool $expectedResult)
    {
        $this->filtersPurifier->expects($this->never())
            ->method('purifyFilters');
        $result = $this->definitionConverter->hasFilters($definition);
        $this->assertEquals($expectedResult, $result);
    }

    public function hasFiltersProvider(): array
    {
        return [
            'when definition is array without filters' => [
                'definition' => ['someKey'],
                'expectedResult' => false,
            ],
            'when definition is json without filters' => [
                'definition' => json_encode(['someKey']),
                'expectedResult' => false,
            ],
            'when definition is array with filters' => [
                'definition' => ['filters' => ['some filter']],
                'expectedResult' => true,
            ],
            'when definition is json with filters' => [
                'definition' => json_encode(['filters' => ['some filter']]),
                'expectedResult' => true,
            ],
        ];
    }
}
