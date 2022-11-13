<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Expression\Autocomplete;

use Oro\Bundle\ProductBundle\Expression\Autocomplete\AutocompleteFieldsProvider;

class AutocompleteFieldsProviderTest extends AbstractFieldsProviderTest
{
    /** @var AutocompleteFieldsProvider */
    private $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new AutocompleteFieldsProvider(
            $this->expressionParser,
            $this->fieldsProvider,
            $this->translator
        );
    }

    /**
     * @dataProvider getFieldsDataProvider
     */
    public function testGetAutocompleteDataNumericOnly(array $fieldsData)
    {
        $numericalOnly = true;
        $withRelations = true;

        $this->configureDependencies($fieldsData, $numericalOnly, $withRelations);

        $expectedData = [
            AutocompleteFieldsProvider::ROOT_ENTITIES_KEY => [
                'ProductClass' => 'product'
            ],
            AutocompleteFieldsProvider::FIELDS_DATA_KEY => [
                'ProductClass' => [
                    'numeric' => [
                        'label' => 'min_quantity.label TRANS',
                        'type' => 'float'
                    ],
                    'stock' => [
                        'label' => 'stock.label TRANS',
                        'type' => 'relation',
                        'relation_alias' => 'StockClass'
                    ]
                ],
                'StockClass' => [
                    'qty' => [
                        'label' => 'quantity.label TRANS',
                        'type' => 'integer'
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
        $this->assertEquals($expectedData, $this->provider->getAutocompleteData($numericalOnly, $withRelations));
    }

    /**
     * @dataProvider getFieldsDataProvider
     */
    public function testGetAutocompleteData(array $fieldsData)
    {
        $numericalOnly = false;
        $withRelations = true;

        $this->configureDependencies($fieldsData, $numericalOnly, $withRelations);

        $expectedData = [
            AutocompleteFieldsProvider::ROOT_ENTITIES_KEY => [
                'ProductClass' => 'product'
            ],
            AutocompleteFieldsProvider::FIELDS_DATA_KEY => [
                'ProductClass' => [
                    'id' => [
                        'label' => 'id.label TRANS',
                        'type' => AutocompleteFieldsProvider::TYPE_INTEGER
                    ],
                    'unit' => [
                        'label' => 'unit.label TRANS',
                        'type' => AutocompleteFieldsProvider::TYPE_RELATION,
                        'relation_alias' => 'UnitClass'
                    ],
                    'stock' => [
                        'label' => 'stock.label TRANS',
                        'type' => 'relation',
                        'relation_alias' => 'StockClass'
                    ],
                    'numeric' => [
                        'label' => 'min_quantity.label TRANS',
                        'type' => 'standalone'
                    ]
                ],
                'UnitClass' => [
                    'special' => [
                        'label' => 'special TRANS',
                        'type' => 'collection'
                    ],
                    'owner' => [
                        'label' => 'owner.label TRANS',
                        'type' => 'relation',
                        'relation_alias' => 'UserClass',
                    ]
                ],
                'UserClass' => [
                    'id' => [
                        'label' => 'id.label TRANS',
                        'type' => AutocompleteFieldsProvider::TYPE_INTEGER
                    ],
                    'owner' => [
                        'label' => 'owner.label TRANS',
                        'type' => 'relation',
                        'relation_alias' => 'UserClass',
                    ]
                ],
                'StockClass' => [
                    'id' => [
                        'label' => 'id.label TRANS',
                        'type' => AutocompleteFieldsProvider::TYPE_INTEGER
                    ],
                    'qty' => [
                        'label' => 'quantity.label TRANS',
                        'type' => 'integer'
                    ],
                    'owner' => [
                        'label' => 'owner.label TRANS',
                        'type' => 'relation',
                        'relation_alias' => 'UserClass',
                    ]
                ]
            ]
        ];

        $this->provider->addSpecialFieldInformation(
            'UnitClass',
            'special',
            ['label' => 'special', 'type' => 'collection']
        );
        $this->provider->addSpecialFieldInformation('ProductClass', 'numeric', ['type' => 'standalone']);
        $this->assertEquals($expectedData, $this->provider->getAutocompleteData($numericalOnly, $withRelations));
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
