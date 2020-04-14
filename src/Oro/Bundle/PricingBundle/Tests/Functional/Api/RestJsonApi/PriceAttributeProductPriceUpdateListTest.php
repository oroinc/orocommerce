<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpdateListTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceAttributeProductPrices;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * @dbIsolationPerTest
 */
class PriceAttributeProductPriceUpdateListTest extends RestJsonApiUpdateListTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadPriceAttributeProductPrices::class]);
    }

    public function testCreateEntities()
    {
        $data = [
            'data' => [
                [
                    'type'          => 'priceattributeproductprices',
                    'attributes'    => [
                        'value'    => '24.5700',
                        'currency' => 'USD'
                    ],
                    'relationships' => [
                        'priceList' => [
                            'data' => [
                                'type' => 'priceattributepricelists',
                                'id'   => '<toString(@price_attribute_price_list_2->id)>'
                            ]
                        ],
                        'product'   => [
                            'data' => [
                                'type' => 'products',
                                'id'   => '<toString(@product-3->id)>',
                            ]
                        ],
                        'unit'      => [
                            'data' => [
                                'type' => 'productunits',
                                'id'   => '<toString(@product_unit.liter->code)>'
                            ]
                        ]
                    ]
                ],
                [
                    'type'          => 'priceattributeproductprices',
                    'attributes'    => [
                        'value'    => '59.0000',
                        'currency' => 'USD'
                    ],
                    'relationships' => [
                        'priceList' => [
                            'data' => [
                                'type' => 'priceattributepricelists',
                                'id'   => '<toString(@price_attribute_price_list_1->id)>'
                            ]
                        ],
                        'product'   => [
                            'data' => [
                                'type' => 'products',
                                'id'   => '<toString(@product-2->id)>'
                            ]
                        ],
                        'unit'      => [
                            'data' => [
                                'type' => 'productunits',
                                'id'   => '<toString(@product_unit.box->code)>'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $this->processUpdateList(PriceAttributeProductPrice::class, $data);

        $response = $this->cget(
            ['entity' => 'priceattributeproductprices'],
            ['filter[id][gt]' => '@price_attribute_product_price.14->id']
        );
        $expectedData = $data;
        foreach ($expectedData['data'] as $key => $item) {
            $expectedData['data'][$key]['id'] = 'new';
        }
        $responseContent = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testUpdateEntities()
    {
        $priceAttributeProductPrice1Id = $this->getReference('price_attribute_product_price.13')->getId();
        $priceAttributeProductPrice2Id = $this->getReference('price_attribute_product_price.14')->getId();

        $data = [
            'data' => [
                [
                    'meta'       => ['update' => true],
                    'type'       => 'priceattributeproductprices',
                    'id'         => (string)$priceAttributeProductPrice1Id,
                    'attributes' => [
                        'value' => 123,
                    ]
                ],
                [
                    'meta'       => ['update' => true],
                    'type'       => 'priceattributeproductprices',
                    'id'         => (string)$priceAttributeProductPrice2Id,
                    'attributes' => [
                        'value'    => 456,
                        'currency' => 'USD'
                    ]
                ]
            ],
        ];
        $this->processUpdateList(PriceAttributeProductPrice::class, $data);

        $response = $this->cget(
            ['entity' => 'priceattributeproductprices'],
            ['filter' => ['id' => [(string)$priceAttributeProductPrice1Id, (string)$priceAttributeProductPrice2Id]]]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'priceattributeproductprices',
                        'id'         => (string)$priceAttributeProductPrice1Id,
                        'attributes' => [
                            'value' => '123.0000'
                        ]
                    ],
                    [
                        'type'       => 'priceattributeproductprices',
                        'id'         => (string)$priceAttributeProductPrice2Id,
                        'attributes' => [
                            'value'    => '456.0000',
                            'currency' => 'USD'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testCreateAndUpdateEntities()
    {
        $priceAttributeProductPriceId = $this->getReference('price_attribute_product_price.14')->getId();

        $data = [
            'data' => [
                [
                    'meta'       => ['update' => true],
                    'type'       => 'priceattributeproductprices',
                    'id'         => (string)$priceAttributeProductPriceId,
                    'attributes' => [
                        'value'    => '456.0000',
                        'currency' => 'USD'
                    ]
                ],
                [
                    'type'          => 'priceattributeproductprices',
                    'attributes'    => [
                        'value'    => '24.5700',
                        'currency' => 'USD'
                    ],
                    'relationships' => [
                        'priceList' => [
                            'data' => [
                                'type' => 'priceattributepricelists',
                                'id'   => '<toString(@price_attribute_price_list_2->id)>'
                            ]
                        ],
                        'product'   => [
                            'data' => [
                                'type' => 'products',
                                'id'   => '<toString(@product-3->id)>'
                            ]
                        ],
                        'unit'      => [
                            'data' => [
                                'type' => 'productunits',
                                'id'   => '<toString(@product_unit.liter->code)>'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $this->processUpdateList(PriceAttributeProductPrice::class, $data);

        $response = $this->cget(
            ['entity' => 'priceattributeproductprices'],
            ['filter[id][gte]' => '@price_attribute_product_price.14->id']
        );
        $expectedData = $data;
        unset($expectedData['data'][0]['meta']);
        $expectedData['data'][1]['id'] = 'new';
        $responseContent = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($responseContent, $response);
    }

    public function testCreateEntitiesWithIncludes()
    {
        $this->processUpdateList(
            PriceAttributeProductPrice::class,
            'price_attribute_product_price/update_list_create_with_includes.yml'
        );

        $response = $this->cget(
            ['entity' => 'priceattributeproductprices'],
            ['filter[id][gt]' => '@price_attribute_product_price.14->id']
        );
        $expectedData = [
            'data' => [
                [
                    'type'          => 'priceattributeproductprices',
                    'id'            => 'new',
                    'attributes'    => [
                        'value'    => '24.5700',
                        'currency' => 'USD'
                    ],
                    'relationships' => [
                        'priceList' => [
                            'data' => [
                                'type' => 'priceattributepricelists',
                                'id'   => '<toString(@price_attribute_price_list_2->id)>'
                            ]
                        ],
                        'product'   => [
                            'data' => [
                                'type' => 'products',
                                'id'   => 'new'
                            ]
                        ],
                        'unit'      => [
                            'data' => [
                                'type' => 'productunits',
                                'id'   => '<toString(@product_unit.liter->code)>'
                            ]
                        ]
                    ]
                ],
                [
                    'type'          => 'priceattributeproductprices',
                    'id'            => 'new',
                    'attributes'    => [
                        'value'    => '678.9000',
                        'currency' => 'USD'
                    ],
                    'relationships' => [
                        'priceList' => [
                            'data' => [
                                'type' => 'priceattributepricelists',
                                'id'   => '<toString(@price_attribute_price_list_2->id)>'
                            ]
                        ],
                        'product'   => [
                            'data' => [
                                'type' => 'products',
                                'id'   => 'new'
                            ]
                        ],
                        'unit'      => [
                            'data' => [
                                'type' => 'productunits',
                                'id'   => '<toString(@product_unit.box->code)>'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $responseContent = $this->updateResponseContent($expectedData, $response);
        $this->assertResponseContains($responseContent, $response);

        $productRepository = $this->getEntityManager()->getRepository(Product::class);
        self::assertEquals('Test product 1', $productRepository->findOneBy(['sku' => 'test-api-01'])->getName());
        self::assertEquals('Test product 2', $productRepository->findOneBy(['sku' => 'test-api-02'])->getName());
    }
}
