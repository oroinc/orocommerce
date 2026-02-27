<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpdateListTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRuleLexemes;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPricesWithRules;

/**
 * @dbIsolationPerTest
 */
class ProductPriceSyncModeUpdateListTest extends RestJsonApiUpdateListTestCase
{
    use ProductPriceTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([LoadProductPricesWithRules::class, LoadPriceRuleLexemes::class]);

        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.product_price_cpl');
        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.price_list_to_product');
        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.price_list_currency');
    }

    private function arrangeExpectedData(array &$expectedData, array $responseContent, int $firstItemQuantity): array
    {
        // we cannot rely to order of returned data due to product price ID is UUID
        if (isset($responseContent['data'][0]['attributes']['quantity'])
            && \count($responseContent['data']) === 2
            && $responseContent['data'][0]['attributes']['quantity'] !== $firstItemQuantity
        ) {
            $tmp = $expectedData['data'][0];
            $expectedData['data'][0] = $expectedData['data'][1];
            $expectedData['data'][1] = $tmp;
        }

        return $expectedData;
    }

    public function testCreateEntities(): void
    {
        $priceList5Id = $this->getReference('price_list_5')->getId();
        $data = [
            'data' => [
                [
                    'type' => 'productprices',
                    'attributes' => [
                        'quantity' => 250,
                        'value' => '150.0000',
                        'currency' => 'EUR'
                    ],
                    'relationships' => [
                        'priceList' => [
                            'data' => ['type' => 'pricelists', 'id' => (string)$priceList5Id]
                        ],
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product-5->id)>']
                        ],
                        'unit' => [
                            'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.milliliter->code)>']
                        ]
                    ]
                ],
                [
                    'type' => 'productprices',
                    'attributes' => [
                        'quantity' => 10,
                        'value' => '20.0000',
                        'currency' => 'GBP'
                    ],
                    'relationships' => [
                        'priceList' => [
                            'data' => ['type' => 'pricelists', 'id' => (string)$priceList5Id]
                        ],
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product-1->id)>']
                        ],
                        'unit' => [
                            'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.bottle->code)>']
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->sendUpdateListRequestWithoutMessageQueueAndWithSynchronousMode(ProductPrice::class, $data);

        $responseContent = self::jsonToArray($response->getContent());
        $expectedData = $this->arrangeExpectedData($data, $responseContent, 250);
        $expectedData['data'][0]['id'] = $responseContent['data'][0]['id'];
        $expectedData['data'][1]['id'] = $responseContent['data'][1]['id'];
        $this->assertResponseContains($expectedData, $response);
    }

    public function testUpdateEntities(): void
    {
        $priceList1Id = $this->getReference('price_list_1')->getId();
        $productPrice1Id = $this->getReference(LoadProductPricesWithRules::PRODUCT_PRICE_1)->getId();
        $productPrice1ApiId = $productPrice1Id . '-' . $priceList1Id;
        $productPrice2Id = $this->getReference(LoadProductPricesWithRules::PRODUCT_PRICE_2)->getId();
        $productPrice2ApiId = $productPrice2Id . '-' . $priceList1Id;
        $data = [
            'data' => [
                [
                    'meta' => ['update' => true],
                    'type' => 'productprices',
                    'id' => $productPrice1ApiId,
                    'attributes' => [
                        'quantity' => 250,
                        'value' => '150.0000',
                        'currency' => 'CAD'
                    ],
                    'relationships' => [
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product-5->id)>']
                        ],
                        'unit' => [
                            'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.milliliter->code)>']
                        ]
                    ]
                ],
                [
                    'meta' => ['update' => true],
                    'type' => 'productprices',
                    'id' => $productPrice2ApiId,
                    'attributes' => [
                        'quantity' => 10,
                        'value' => '20.0000',
                        'currency' => 'USD'
                    ],
                    'relationships' => [
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product-3->id)>']
                        ],
                        'unit' => [
                            'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.liter->code)>']
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->sendUpdateListRequestWithoutMessageQueueAndWithSynchronousMode(ProductPrice::class, $data);

        $responseContent = self::jsonToArray($response->getContent());
        $expectedData = $this->arrangeExpectedData($data, $responseContent, 250);
        unset($expectedData['data'][0]['meta'], $expectedData['data'][1]['meta']);
        $this->assertResponseContains($expectedData, $response);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateEntitiesWithIncludes(): void
    {
        $data = [
            'data' => [
                [
                    'type' => 'productprices',
                    'attributes' => [
                        'quantity' => 250,
                        'value' => '150.0000',
                        'currency' => 'EUR'
                    ],
                    'relationships' => [
                        'priceList' => [
                            'data' => ['type' => 'pricelists', 'id' => 'new_price_list']
                        ],
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product-5->id)>']
                        ],
                        'unit' => [
                            'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.milliliter->code)>']
                        ]
                    ]
                ],
                [
                    'type' => 'productprices',
                    'attributes' => [
                        'quantity' => 10,
                        'value' => '20.0000',
                        'currency' => 'GBP'
                    ],
                    'relationships' => [
                        'priceList' => [
                            'data' => ['type' => 'pricelists', 'id' => 'new_price_list']
                        ],
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product-1->id)>']
                        ],
                        'unit' => [
                            'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.bottle->code)>']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type' => 'pricelists',
                    'id' => 'new_price_list',
                    'attributes' => [
                        'active' => true,
                        'name' => 'New Price List 1',
                        'priceListCurrencies' => ['EUR', 'GBP']
                    ]
                ]
            ]
        ];
        $response = $this->sendUpdateListRequestWithoutMessageQueueAndWithSynchronousMode(ProductPrice::class, $data);

        $priceListId = $this->getEntityManager(PriceList::class)
            ->getRepository(PriceList::class)
            ->findOneBy(['name' => 'New Price List 1'])
            ->getId();

        $responseContent = self::jsonToArray($response->getContent());
        $expectedData = $this->arrangeExpectedData($data, $responseContent, 250);
        $expectedData['data'][0]['id'] = $responseContent['data'][0]['id'];
        $expectedData['data'][1]['id'] = $responseContent['data'][1]['id'];
        $expectedData['data'][0]['relationships']['priceList']['data']['id'] = (string)$priceListId;
        $expectedData['data'][1]['relationships']['priceList']['data']['id'] = (string)$priceListId;
        $expectedData['included'][0]['id'] = (string)$priceListId;
        $this->assertResponseContains($expectedData, $response);
    }

    public function testUpdateResetPriceRule(): void
    {
        $priceList1Id = $this->getReference('price_list_1')->getId();
        $productPrice1Id = $this->getReference(LoadProductPricesWithRules::PRODUCT_PRICE_1)->getId();
        $productPrice1ApiId = $productPrice1Id . '-' . $priceList1Id;
        $data = [
            'data' => [
                [
                    'meta' => ['update' => true],
                    'type' => 'productprices',
                    'id' => $productPrice1ApiId,
                    'attributes' => [
                        'value' => '150.0000'
                    ]
                ]
            ]
        ];
        $this->sendUpdateListRequestWithoutMessageQueueAndWithSynchronousMode(ProductPrice::class, $data);

        $productPrice = $this->findProductPriceByUniqueKey(
            5,
            'USD',
            $this->getReference('price_list_1'),
            $this->getReference('product-1'),
            $this->getReference('product_unit.liter')
        );

        self::assertNull($productPrice->getPriceRule());
    }

    public function testCreateEntitiesForSeveralPriceLists(): void
    {
        $priceList1Id = $this->getReference('price_list_1')->getId();
        $priceList5Id = $this->getReference('price_list_5')->getId();
        $data = [
            'data' => [
                [
                    'type' => 'productprices',
                    'attributes' => [
                        'quantity' => 250,
                        'value' => '150.0000',
                        'currency' => 'EUR'
                    ],
                    'relationships' => [
                        'priceList' => [
                            'data' => ['type' => 'pricelists', 'id' => (string)$priceList1Id]
                        ],
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product-5->id)>']
                        ],
                        'unit' => [
                            'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.milliliter->code)>']
                        ]
                    ]
                ],
                [
                    'type' => 'productprices',
                    'attributes' => [
                        'quantity' => 10,
                        'value' => '20.0000',
                        'currency' => 'GBP'
                    ],
                    'relationships' => [
                        'priceList' => [
                            'data' => ['type' => 'pricelists', 'id' => (string)$priceList5Id]
                        ],
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product-1->id)>']
                        ],
                        'unit' => [
                            'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.bottle->code)>']
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->sendUpdateListRequestWithoutMessageQueueAndWithSynchronousMode(ProductPrice::class, $data);

        $responseContent = self::jsonToArray($response->getContent());
        $expectedData = $this->arrangeExpectedData($data, $responseContent, 250);
        $expectedData['data'][0]['id'] = $responseContent['data'][0]['id'];
        $expectedData['data'][1]['id'] = $responseContent['data'][1]['id'];
        $this->assertResponseContains($expectedData, $response);
    }

    public function testUpdateEntitiesForSeveralPriceLists(): void
    {
        $priceList1Id = $this->getReference('price_list_1')->getId();
        $priceList2Id = $this->getReference('price_list_2')->getId();
        $productPrice1Id = $this->getReference(LoadProductPricesWithRules::PRODUCT_PRICE_1)->getId();
        $productPrice1ApiId = $productPrice1Id . '-' . $priceList1Id;
        $productPrice2Id = $this->getReference(LoadProductPricesWithRules::PRODUCT_PRICE_3)->getId();
        $productPrice2ApiId = $productPrice2Id . '-' . $priceList2Id;
        $data = [
            'data' => [
                [
                    'meta' => ['update' => true],
                    'type' => 'productprices',
                    'id' => $productPrice1ApiId,
                    'attributes' => [
                        'quantity' => 250,
                        'value' => '150.0000',
                        'currency' => 'CAD'
                    ],
                    'relationships' => [
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product-5->id)>']
                        ],
                        'unit' => [
                            'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.milliliter->code)>']
                        ]
                    ]
                ],
                [
                    'meta' => ['update' => true],
                    'type' => 'productprices',
                    'id' => $productPrice2ApiId,
                    'attributes' => [
                        'quantity' => 10,
                        'value' => '20.0000',
                        'currency' => 'USD'
                    ],
                    'relationships' => [
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product-3->id)>']
                        ],
                        'unit' => [
                            'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.liter->code)>']
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->sendUpdateListRequestWithoutMessageQueueAndWithSynchronousMode(ProductPrice::class, $data);

        $responseContent = self::jsonToArray($response->getContent());
        $expectedData = $this->arrangeExpectedData($data, $responseContent, 250);
        unset($expectedData['data'][0]['meta'], $expectedData['data'][1]['meta']);
        $this->assertResponseContains($expectedData, $response);
    }
}
