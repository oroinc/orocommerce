<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\JsonApiDocContainsConstraint;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiUpdateListTestCase;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\ProductPrice;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadPriceRuleLexemes;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPricesWithRules;

/**
 * @dbIsolationPerTest
 */
class ProductPriceUpdateListTest extends RestJsonApiUpdateListTestCase
{
    use ProductPriceTestTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadProductPricesWithRules::class, LoadPriceRuleLexemes::class]);

        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.product_price_cpl');
        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.price_list_to_product');
        $this->getOptionalListenerManager()->enableListener('oro_pricing.entity_listener.price_list_currency');
    }

    public function testCreateEntities()
    {
        $priceList5Id = $this->getReference('price_list_5')->getId();
        $data = [
            'data' => [
                [
                    'type'          => 'productprices',
                    'attributes'    => [
                        'quantity' => 250,
                        'value'    => '150.0000',
                        'currency' => 'EUR'
                    ],
                    'relationships' => [
                        'priceList' => [
                            'data' => ['type' => 'pricelists', 'id' => (string)$priceList5Id]
                        ],
                        'product'   => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product-5->id)>']
                        ],
                        'unit'      => [
                            'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.milliliter->code)>']
                        ]
                    ]
                ],
                [
                    'type'          => 'productprices',
                    'attributes'    => [
                        'quantity' => 10,
                        'value'    => '20.0000',
                        'currency' => 'GBP'
                    ],
                    'relationships' => [
                        'priceList' => [
                            'data' => ['type' => 'pricelists', 'id' => (string)$priceList5Id]
                        ],
                        'product'   => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product-1->id)>']
                        ],
                        'unit'      => [
                            'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.bottle->code)>']
                        ]
                    ]
                ]
            ]
        ];
        $this->processUpdateList(ProductPrice::class, $data);

        $response = $this->cget(['entity' => 'productprices'], ['filter[priceList]' => '@price_list_5->id']);
        // we cannot rely to order of returned data due to product price ID is UUID
        $responseContent = self::jsonToArray($response->getContent());
        if (isset($responseContent['data'][0]['attributes']['quantity'])
            && count($responseContent['data']) === 2
            && $responseContent['data'][0]['attributes']['quantity'] !== 250
        ) {
            $tmp = $responseContent['data'][0];
            $responseContent['data'][0] = $responseContent['data'][1];
            $responseContent['data'][1] = $tmp;
        }
        $expectedData = $data;
        $expectedData['data'][0]['id'] = $responseContent['data'][0]['id'];
        $expectedData['data'][1]['id'] = $responseContent['data'][1]['id'];
        self::assertThat(
            $responseContent,
            new JsonApiDocContainsConstraint(self::processTemplateData($this->getResponseData($expectedData)))
        );
    }

    public function testUpdateEntities()
    {
        $priceList1Id = $this->getReference('price_list_1')->getId();
        $productPrice1Id = $this->getReference(LoadProductPricesWithRules::PRODUCT_PRICE_1)->getId();
        $productPrice1ApiId = $productPrice1Id . '-' . $priceList1Id;
        $productPrice2Id = $this->getReference(LoadProductPricesWithRules::PRODUCT_PRICE_2)->getId();
        $productPrice2ApiId = $productPrice2Id . '-' . $priceList1Id;
        $data = [
            'data' => [
                [
                    'meta'          => ['update' => true],
                    'type'          => 'productprices',
                    'id'            => $productPrice1ApiId,
                    'attributes'    => [
                        'quantity' => 250,
                        'value'    => '150.0000',
                        'currency' => 'CAD'
                    ],
                    'relationships' => [
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product-5->id)>']
                        ],
                        'unit'    => [
                            'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.milliliter->code)>']
                        ]
                    ]
                ],
                [
                    'meta'          => ['update' => true],
                    'type'          => 'productprices',
                    'id'            => $productPrice2ApiId,
                    'attributes'    => [
                        'quantity' => 10,
                        'value'    => '20.0000',
                        'currency' => 'USD'
                    ],
                    'relationships' => [
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product-3->id)>']
                        ],
                        'unit'    => [
                            'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.liter->code)>']
                        ]
                    ]
                ]
            ]
        ];
        $this->processUpdateList(ProductPrice::class, $data);

        $response = $this->cget(['entity' => 'productprices'], ['filter[priceList]' => (string)$priceList1Id]);
        // we cannot rely to order of returned data due to product price ID is UUID
        $responseContent = self::jsonToArray($response->getContent());
        if (isset($responseContent['data'][0]['attributes']['quantity'])
            && count($responseContent['data']) === 2
            && $responseContent['data'][0]['attributes']['quantity'] !== 250
        ) {
            $tmp = $responseContent['data'][0];
            $responseContent['data'][0] = $responseContent['data'][1];
            $responseContent['data'][1] = $tmp;
        }
        $expectedData = $data;
        unset($expectedData['data'][0]['meta'], $expectedData['data'][1]['meta']);
        self::assertThat(
            $responseContent,
            new JsonApiDocContainsConstraint(self::processTemplateData($this->getResponseData($expectedData)))
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testCreateEntitiesWithIncludes()
    {
        $data = [
            'data'     => [
                [
                    'type'          => 'productprices',
                    'attributes'    => [
                        'quantity' => 250,
                        'value'    => '150.0000',
                        'currency' => 'EUR'
                    ],
                    'relationships' => [
                        'priceList' => [
                            'data' => ['type' => 'pricelists', 'id' => 'new_price_list']
                        ],
                        'product'   => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product-5->id)>']
                        ],
                        'unit'      => [
                            'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.milliliter->code)>']
                        ]
                    ]
                ],
                [
                    'type'          => 'productprices',
                    'attributes'    => [
                        'quantity' => 10,
                        'value'    => '20.0000',
                        'currency' => 'GBP'
                    ],
                    'relationships' => [
                        'priceList' => [
                            'data' => ['type' => 'pricelists', 'id' => 'new_price_list']
                        ],
                        'product'   => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product-1->id)>']
                        ],
                        'unit'      => [
                            'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.bottle->code)>']
                        ]
                    ]
                ]
            ],
            'included' => [
                [
                    'type'       => 'pricelists',
                    'id'         => 'new_price_list',
                    'attributes' => [
                        'active'              => true,
                        'name'                => 'New Price List 1',
                        'priceListCurrencies' => ['EUR', 'GBP']
                    ]
                ]
            ]
        ];
        $this->processUpdateList(ProductPrice::class, $data);

        $priceListId = $this->getEntityManager(PriceList::class)
            ->getRepository(PriceList::class)
            ->findOneBy(['name' => 'New Price List 1'])
            ->getId();

        $response = $this->cget(
            ['entity' => 'productprices'],
            ['filter[priceList]' => $priceListId, 'include' => 'priceList']
        );
        // we cannot rely to order of returned data due to product price ID is UUID
        $responseContent = self::jsonToArray($response->getContent());
        if (isset($responseContent['data'][0]['attributes']['quantity'])
            && count($responseContent['data']) === 2
            && $responseContent['data'][0]['attributes']['quantity'] !== 250
        ) {
            $tmp = $responseContent['data'][0];
            $responseContent['data'][0] = $responseContent['data'][1];
            $responseContent['data'][1] = $tmp;
        }
        $expectedData = $data;
        $expectedData['data'][0]['id'] = $responseContent['data'][0]['id'];
        $expectedData['data'][1]['id'] = $responseContent['data'][1]['id'];
        $expectedData['data'][0]['relationships']['priceList']['data']['id'] = (string)$priceListId;
        $expectedData['data'][1]['relationships']['priceList']['data']['id'] = (string)$priceListId;
        $expectedData['included'][0]['id'] = (string)$priceListId;
        self::assertThat(
            $responseContent,
            new JsonApiDocContainsConstraint(self::processTemplateData($this->getResponseData($expectedData)))
        );
    }

    public function testUpdateResetPriceRule()
    {
        $priceList1Id = $this->getReference('price_list_1')->getId();
        $productPrice1Id = $this->getReference(LoadProductPricesWithRules::PRODUCT_PRICE_1)->getId();
        $productPrice1ApiId = $productPrice1Id . '-' . $priceList1Id;
        $data = [
            'data' => [
                [
                    'meta'          => ['update' => true],
                    'type'          => 'productprices',
                    'id'            => $productPrice1ApiId,
                    'attributes'    => [
                        'value'    => '150.0000'
                    ]
                ]
            ]
        ];
        $this->processUpdateList(ProductPrice::class, $data);

        $productPrice = $this->findProductPriceByUniqueKey(
            5,
            'USD',
            $this->getReference('price_list_1'),
            $this->getReference('product-1'),
            $this->getReference('product_unit.liter')
        );

        self::assertNull($productPrice->getPriceRule());
    }
}
