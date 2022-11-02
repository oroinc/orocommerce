<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Entity\Repository\PriceAttributePriceListRepository;
use Oro\Bundle\PricingBundle\Provider\ProductVirtualRelationProvider;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductVirtualRelationProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductVirtualRelationProvider */
    private $provider;

    protected function setUp(): void
    {
        $repository = $this->createMock(PriceAttributePriceListRepository::class);
        $repository->expects($this->any())
            ->method('getFieldNames')
            ->willReturn([
                [
                    'id' => 1,
                    'fieldName' => 'msrp',
                    'name' => 'MSRP'
                ],
                [
                    'id' => 2,
                    'fieldName' => 'map',
                    'name' => 'MAP'
                ]
            ]);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(PriceAttributePriceList::class)
            ->willReturn($repository);

        $this->provider = new ProductVirtualRelationProvider($doctrineHelper);
    }

    /**
     * @dataProvider isVirtualRelationProvider
     */
    public function testIsVirtualRelation(string $class, string $field, bool $expected)
    {
        $this->assertEquals($expected, $this->provider->isVirtualRelation($class, $field));
    }

    public function isVirtualRelationProvider(): array
    {
        return [
            [Product::class, 'msrp', true],
            [Product::class, 'wrong_test_attribute', false],
            ['stdClass', 'relation', false],
        ];
    }

    /**
     * @dataProvider getVirtualRelationQueryProvider
     */
    public function testGetVirtualRelationQuery(string $class, string $field, array $expected)
    {
        $this->assertEquals($expected, $this->provider->getVirtualRelationQuery($class, $field));
    }

    public function getVirtualRelationQueryProvider(): array
    {
        return [
            [
                Product::class,
                'msrp',
                [
                    'join' => [
                        'left' => [
                            [
                                'join' => PriceAttributeProductPrice::class,
                                'alias' => 'msrpPrice',
                                'conditionType' => 'WITH',
                                'condition' => '(msrpPrice.product = entity and msrpPrice.priceList = 1)',
                            ]
                        ],
                    ],
                ],
            ],
            ['stdClass', 'msrp', []],
        ];
    }

    /**
     * @dataProvider getVirtualRelationsProvider
     */
    public function testGetVirtualRelations(string $class, array $expected)
    {
        $this->assertEquals($expected, $this->provider->getVirtualRelations($class));
    }

    public function getVirtualRelationsProvider(): array
    {
        return [
            [
                Product::class,
                [
                    'msrp' => [
                        'label' => 'MSRP',
                        'relation_type' => 'manyToOne',
                        'related_entity_name' => PriceAttributeProductPrice::class,
                        'target_join_alias' => 'msrpPrice',
                        'query' => [
                            'join' => [
                                'left' => [
                                    [
                                        'join' => PriceAttributeProductPrice::class,
                                        'alias' => 'msrpPrice',
                                        'conditionType' => 'WITH',
                                        'condition' => '(msrpPrice.product = entity and msrpPrice.priceList = 1)',
                                    ]
                                ],
                            ],
                        ],
                    ],
                    'map' => [
                        'label' => 'MAP',
                        'relation_type' => 'manyToOne',
                        'related_entity_name' => PriceAttributeProductPrice::class,
                        'target_join_alias' => 'mapPrice',
                        'query' => [
                            'join' => [
                                'left' => [
                                    [
                                        'join' => PriceAttributeProductPrice::class,
                                        'alias' => 'mapPrice',
                                        'conditionType' => 'WITH',
                                        'condition' => '(mapPrice.product = entity and mapPrice.priceList = 2)',
                                    ]
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            ['stdClass', []],
        ];
    }

    /**
     * @dataProvider getTargetJoinAliasDataProvider
     */
    public function testGetTargetJoinAlias(string $fieldName, string $joinAlias)
    {
        $this->assertEquals($joinAlias, $this->provider->getTargetJoinAlias(Product::class, $fieldName));
    }

    public function getTargetJoinAliasDataProvider(): array
    {
        return [
            [
                'fieldName' => 'msrp',
                'joinAlias' => 'msrpPrice',
            ],
            [
                'fieldName' => 'map',
                'joinAlias' => 'mapPrice',
            ],
        ];
    }
}
