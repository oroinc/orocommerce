<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Expression\Autocomplete;

use Oro\Bundle\ProductBundle\Expression\Autocomplete\CurrencyFieldsProvider;
use Oro\Bundle\ProductBundle\Expression\Autocomplete\UnitFieldsProvider;

class CurrencyFieldsProviderTest extends AbstractFieldsProviderTest
{
    /** @var UnitFieldsProvider */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new CurrencyFieldsProvider(
            $this->expressionParser,
            $this->fieldsProvider,
            $this->translator
        );
    }

    public function testGetDataProviderConfigNumericOnly()
    {
        $this->configureDependencies([], true, true);

        $expected = [
            'fieldsFilterWhitelist' => [],
            'isRestrictiveWhitelist' => true,
        ];
        $this->assertEquals($expected, $this->provider->getDataProviderConfig(true));
    }

    /**
     * @dataProvider getFieldsDataProvider
     */
    public function testGetDataProviderConfig(array $fieldsData)
    {
        $this->configureDependencies($fieldsData, false, true);

        $expected = [
            'fieldsFilterWhitelist' => [
                'ProductClass' => [
                    'currency' => true,
                    'stock' => true,
                ],
                'StockClass' => [
                    'original_currency' => true,
                ],
            ],
            'isRestrictiveWhitelist' => true,
        ];
        $this->assertEquals($expected, $this->provider->getDataProviderConfig());
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
