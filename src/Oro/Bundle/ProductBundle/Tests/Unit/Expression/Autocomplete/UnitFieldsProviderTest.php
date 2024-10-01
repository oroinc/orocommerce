<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Expression\Autocomplete;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Expression\Autocomplete\UnitFieldsProvider;

class UnitFieldsProviderTest extends AbstractFieldsProviderTest
{
    /** @var UnitFieldsProvider */
    private $provider;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new UnitFieldsProvider(
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
                    'unit' => true,
                    'stock' => true,
                ],
                'StockClass' => [
                    'unit' => true,
                ],
            ],
            'isRestrictiveWhitelist' => true,
            'fieldsDataUpdate' => [
                'ProductClass' => [
                    'unit' => [
                        'type' => 'string',
                        'relationType' => null,
                        'relatedEntityName' => ''
                    ],
                ],
                'StockClass' => [
                    'unit' => [
                        'type' => 'string',
                        'relationType' => null,
                        'relatedEntityName' => ''
                    ],
                ],
            ],
        ];
        $this->assertEquals($expected, $this->provider->getDataProviderConfig());
    }

    #[\Override]
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
