<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Expression\Autocomplete;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Expression\Autocomplete\UnitFieldsProvider;

class UnitFieldsProviderTest extends AbstractFieldsProviderTest
{
    /** @var UnitFieldsProvider */
    private $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new UnitFieldsProvider(
            $this->expressionParser,
            $this->fieldsProvider,
            $this->translator
        );
    }

    public function testGetAutocompleteDataNumericOnly()
    {
        $this->configureDependencies([], true, true);

        $expected = [
            UnitFieldsProvider::ROOT_ENTITIES_KEY => [
                'ProductClass' => 'product'
            ],
            UnitFieldsProvider::FIELDS_DATA_KEY => [],
        ];
        $this->assertEquals($expected, $this->provider->getAutocompleteData(true));
    }

    /**
     * @dataProvider getFieldsDataProvider
     */
    public function testGetAutocompleteData(array $fieldsData)
    {
        $this->configureDependencies($fieldsData, false, true);

        $expected = [
            UnitFieldsProvider::ROOT_ENTITIES_KEY => [
                'ProductClass' => 'product'
            ],
            UnitFieldsProvider::FIELDS_DATA_KEY => [
                'ProductClass' => [
                    'unit' => [
                        'label' => 'unit.label TRANS',
                        'type' => 'string'
                    ],
                    'stock' => [
                        'label' => 'stock.label TRANS',
                        'relation_alias' => 'StockClass',
                        'type' => 'relation'
                    ]
                ],
                'StockClass' => [
                    'unit' => [
                        'label' => 'unit.label TRANS',
                        'type' => 'string'
                    ]
                ]
            ],
        ];
        $this->assertEquals($expected, $this->provider->getAutocompleteData());
    }

    /**
     * {@inheritDoc}
     */
    protected function getMap(array $fieldsData, bool $numericalOnly, bool $withRelations): array
    {
        $map = [];
        foreach ($fieldsData as $data) {
            $map[] = [
                $data[self::CLASS_NAME],
                $numericalOnly,
                $withRelations,
                $data[self::FIELDS]
            ];
        }

        return $map;
    }

    public function getFieldsDataProvider(): array
    {
        return [
            [
                [
                    [
                        self::CLASS_NAME => 'ProductClass',
                        self::IS_RELATION => false,
                        self::FIELDS => [
                            'id' => [
                                'name' => 'id',
                                'label' => 'id.label',
                                'type' => 'integer'
                            ],
                            'unit' => [
                                'name' => 'unit',
                                'label' => 'unit.label',
                                'type' => 'manyToOne',
                                'relation_type' => 'manyToOne',
                                'related_entity_name' => ProductUnit::class
                            ],
                            'owner' => [
                                'name' => 'owner',
                                'label' => 'owner.label',
                                'type' => 'manyToOne',
                                'relation_type' => 'manyToOne',
                                'related_entity_name' => 'UserClass'
                            ],
                            'stock' => [
                                'name' => 'stock',
                                'label' => 'stock.label',
                                'type' => 'manyToOne',
                                'relation_type' => 'manyToOne',
                                'related_entity_name' => 'StockClass'
                            ],
                            'unknown_type' => [
                                'name' => 'unknown_type',
                                'label' => 'unknown_type.label',
                                'type' => 'unknown'
                            ],
                            'numeric' => [
                                'name' => 'min_quantity',
                                'label' => 'min_quantity.label',
                                'type' => 'float'
                            ]
                        ]
                    ],
                    [
                        self::CLASS_NAME => 'UserClass',
                        self::IS_RELATION => true,
                        self::FIELDS => [
                            'id' => [
                                'name' => 'id',
                                'label' => 'id.label',
                                'type' => 'integer'
                            ],
                            'name' => [
                                'name' => 'name',
                                'label' => 'name.label',
                                'type' => 'string'
                            ]
                        ]
                    ],
                    [
                        self::CLASS_NAME => ProductUnit::class,
                        self::IS_RELATION => true,
                        self::FIELDS => [
                            'code' => [
                                'name' => 'code',
                                'label' => 'code.label',
                                'type' => 'string'
                            ],
                        ],
                    ],
                    [
                        self::CLASS_NAME => 'StockClass',
                        self::IS_RELATION => true,
                        self::FIELDS => [
                            'id' => [
                                'name' => 'id',
                                'label' => 'id.label',
                                'type' => 'integer'
                            ],
                            'unit' => [
                                'name' => 'unit',
                                'label' => 'unit.label',
                                'type' => 'manyToOne',
                                'relation_type' => 'manyToOne',
                                'related_entity_name' => ProductUnit::class
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
