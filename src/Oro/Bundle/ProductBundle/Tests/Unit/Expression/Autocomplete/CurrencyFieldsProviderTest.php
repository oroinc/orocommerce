<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Expression\Autocomplete;

use Oro\Bundle\ProductBundle\Expression\Autocomplete\CurrencyFieldsProvider;
use Oro\Bundle\ProductBundle\Expression\Autocomplete\UnitFieldsProvider;

class CurrencyFieldsProviderTest extends AbstractFieldsProviderTest
{
    /** @var UnitFieldsProvider */
    private $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new CurrencyFieldsProvider(
            $this->expressionParser,
            $this->fieldsProvider,
            $this->translator
        );
    }

    public function testGetAutocompleteDataNumericOnly()
    {
        $this->configureDependencies([], true, true);

        $expected = [
            CurrencyFieldsProvider::ROOT_ENTITIES_KEY => [
                'ProductClass' => 'product'
            ],
            CurrencyFieldsProvider::FIELDS_DATA_KEY => [],
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
            CurrencyFieldsProvider::ROOT_ENTITIES_KEY => [
                'ProductClass' => 'product'
            ],
            CurrencyFieldsProvider::FIELDS_DATA_KEY => [
                'ProductClass' => [
                    'currency' => [
                        'label' => 'currency.label TRANS',
                        'type' => 'string'
                    ],
                    'stock' => [
                        'label' => 'stock.label TRANS',
                        'relation_alias' => 'StockClass',
                        'type' => 'relation'
                    ]
                ],
                'StockClass' => [
                    'original_currency' => [
                        'label' => 'original_currency.label TRANS',
                        'type' => 'string'
                    ],
                ]
            ],
        ];
        $this->assertEquals($expected, $this->provider->getAutocompleteData());
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
                            'currency' => [
                                'name' => 'currency',
                                'label' => 'currency.label',
                                'type' => 'string'
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
                        self::CLASS_NAME => 'StockClass',
                        self::IS_RELATION => true,
                        self::FIELDS => [
                            'id' => [
                                'name' => 'id',
                                'label' => 'id.label',
                                'type' => 'integer'
                            ],
                            'original_currency' => [
                                'name' => 'original_currency',
                                'label' => 'original_currency.label',
                                'type' => 'string'
                            ],
                            'currency_code' => [
                                'name' => 'currency_code',
                                'label' => 'currency_code.label',
                                'type' => 'integer'
                            ],
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
                ]
            ]
        ];
    }
}
