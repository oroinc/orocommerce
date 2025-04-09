<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Expression\Autocomplete;

use Oro\Bundle\ProductBundle\Expression\Autocomplete\AutocompleteFieldsProvider;

class AutocompleteFieldsProviderTest extends AbstractFieldsProviderTest
{
    /** @var AutocompleteFieldsProvider */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new AutocompleteFieldsProvider(
            $this->expressionParser,
            $this->fieldsProvider,
            $this->translator
        );
    }

    public function testGetRootEntities()
    {
        $this->configureDependencies([], true, true);

        $expected = [
            'ProductClass' => 'product',
        ];
        $this->assertEquals($expected, $this->provider->getRootEntities());
    }

    /**
     * @dataProvider getFieldsDataProvider
     */
    public function testGetDataProviderConfigNumericOnly(array $fieldsData)
    {
        $numericalOnly = true;
        $withRelations = true;

        $this->configureDependencies($fieldsData, $numericalOnly, $withRelations);

        $expected = [
            'optionsFilter' => [
                'unidirectional' => false,
                'exclude' => false,
                'identifier' => false,
            ],
            'include' => [
                ['type' => 'integer'],
                ['type' => 'float'],
                ['type' => 'ref-one'],
            ],
            'fieldsDataUpdate' => [
                'UnitClass' => [
                    'price' => [
                        'label' => 'price TRANS',
                        'type' => 'float',
                    ]
                ]
            ]
        ];

        $this->provider->addSpecialFieldInformation(
            'UnitClass',
            'special',
            ['label' => 'special', 'type' => 'collection']
        );
        $this->provider->addSpecialFieldInformation('UnitClass', 'code', ['type' => 'standalone']);
        $this->provider->addSpecialFieldInformation('UnitClass', 'price', ['type' => 'float', 'label' => 'price']);
        $this->assertEquals($expected, $this->provider->getDataProviderConfig($numericalOnly, $withRelations));
    }

    /**
     * @dataProvider getFieldsDataProvider
     */
    public function testGetDataProviderConfig(array $fieldsData)
    {
        $numericalOnly = false;
        $withRelations = true;

        $this->configureDependencies($fieldsData, $numericalOnly, $withRelations);

        $expectedData = [
            'optionsFilter' => [
                'unidirectional' => false,
                'exclude' => false,
            ],
            'include' => [
                ['type' => 'string'],
                ['type' => 'text'],
                ['type' => 'boolean'],
                ['type' => 'enum'],
                ['type' => 'integer'],
                ['type' => 'float'],
                ['type' => 'money'],
                ['type' => 'decimal'],
                ['type' => 'datetime'],
                ['type' => 'date'],
                ['type' => 'ref-one'],
            ],
            'fieldsDataUpdate' => [
                'UnitClass' => [
                    'special' => [
                        'label' => 'special TRANS',
                        'type' => 'collection',
                    ],
                ],
                'ProductClass' => [
                    'numeric' => [
                        'type' => 'standalone'
                    ],
                ],
            ],
        ];

        $this->provider->addSpecialFieldInformation(
            'UnitClass',
            'special',
            ['label' => 'special', 'type' => 'collection']
        );
        $this->provider->addSpecialFieldInformation('ProductClass', 'numeric', ['type' => 'standalone']);
        $this->assertEquals($expectedData, $this->provider->getDataProviderConfig($numericalOnly, $withRelations));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
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
                                'related_entity_name' => 'UnitClass'
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

                        self::CLASS_NAME => 'UnitClass',
                        self::IS_RELATION => true,
                        self::FIELDS => [
                            'owner' => [
                                'name' => 'owner',
                                'label' => 'owner.label',
                                'type' => 'manyToOne',
                                'relation_type' => 'manyToOne',
                                'related_entity_name' => 'UserClass'
                            ]
                        ],
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
                            'owner' => [
                                'name' => 'owner',
                                'label' => 'owner.label',
                                'type' => 'manyToOne',
                                'relation_type' => 'manyToOne',
                                'related_entity_name' => 'UserClass'
                            ]
                        ]
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
                            'qty' => [
                                'name' => 'quantity',
                                'label' => 'quantity.label',
                                'type' => 'integer'
                            ],
                            'owner' => [
                                'name' => 'owner',
                                'label' => 'owner.label',
                                'type' => 'manyToOne',
                                'relation_type' => 'manyToOne',
                                'related_entity_name' => 'UserClass'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
