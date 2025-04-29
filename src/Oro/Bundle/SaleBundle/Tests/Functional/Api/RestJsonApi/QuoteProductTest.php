<?php

namespace Oro\Bundle\SameBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class QuoteProductTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([LoadQuoteData::class]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'quoteproducts']);

        $this->assertResponseContains('cget_quote_product.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'quoteproducts', 'id' => '<toString(@sale.quote.1.product-1->id)>']
        );

        $this->assertResponseContains('get_quote_product.yml', $response);
    }

    public function testCreateWithRequiredDataOnly(): void
    {
        $data = $this->getRequestData('create_quote_product_min.yml');
        $response = $this->post(
            ['entity' => 'quoteproducts'],
            $data
        );

        $quoteProductId = (int)$this->getResourceId($response);
        /** @var QuoteProduct $quoteProduct */
        $quoteProduct = $this->getEntityManager()->find(QuoteProduct::class, $quoteProductId);
        self::assertTrue(null !== $quoteProduct);
        /** @var QuoteProductOffer $offer */
        $offer = $quoteProduct->getQuoteProductOffers()->first();
        self::assertTrue(null !== $offer);

        $expectedData = $data;
        $expectedData['data']['id'] = (string)$quoteProductId;
        $expectedData['data']['attributes']['freeFormProduct'] = 'product-2.names.default';
        $expectedData['data']['attributes']['productSku'] = 'product-2';
        $expectedData['data']['attributes']['comment'] = null;
        $expectedData['data']['attributes']['customerComment'] = null;
        $expectedData['data']['relationships']['quoteProductOffers']['data'][0]['id'] = (string)$offer->getId();
        $expectedData['included'][0]['id'] = (string)$offer->getId();
        $this->assertResponseContains($expectedData, $response);
    }

    public function testCreate(): void
    {
        $data = $this->getRequestData('create_quote_product_min.yml');
        $data['data']['attributes']['comment'] = 'some comment';
        $data['data']['attributes']['customerComment'] = 'some customer comment';
        $data['data']['relationships']['quoteProductRequests']['data'][] = [
            'type' => 'quoteproductrequests',
            'id' => 'request_1'
        ];
        $data['included'][] = [
            'type' => 'quoteproductrequests',
            'id' => 'request_1',
            'attributes' => [
                'quantity' => 1
            ],
            'relationships' => [
                'productUnit' => [
                    'data' => [
                        'type' => 'productunits',
                        'id' => '<toString(@product_unit.liter->code)>'
                    ]
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'quoteproducts'],
            $data
        );

        $quoteProductId = (int)$this->getResourceId($response);
        /** @var QuoteProduct $quoteProduct */
        $quoteProduct = $this->getEntityManager()->find(QuoteProduct::class, $quoteProductId);
        self::assertTrue(null !== $quoteProduct);
        self::assertCount(1, $quoteProduct->getQuoteProductOffers());
        /** @var QuoteProductOffer $offer */
        $offer = $quoteProduct->getQuoteProductOffers()->first();
        self::assertCount(1, $quoteProduct->getQuoteProductRequests());
        /** @var QuoteProductRequest $request */
        $request = $quoteProduct->getQuoteProductRequests()->first();

        $expectedData = $data;
        $expectedData['data']['id'] = (string)$quoteProductId;
        $expectedData['data']['attributes']['freeFormProduct'] = 'product-2.names.default';
        $expectedData['data']['attributes']['productSku'] = 'product-2';
        $expectedData['data']['attributes']['comment'] = 'some comment';
        $expectedData['data']['attributes']['customerComment'] = 'some customer comment';
        $expectedData['data']['relationships']['quoteProductOffers']['data'][0]['id'] = (string)$offer->getId();
        $expectedData['data']['relationships']['quoteProductRequests']['data'][0]['id'] = (string)$request->getId();
        $expectedData['included'][0]['id'] = (string)$offer->getId();
        $expectedData['included'][1]['id'] = (string)$request->getId();
        $this->assertResponseContains($expectedData, $response);
    }

    public function testCreateForFreeFormProduct(): void
    {
        $data = $this->getRequestData('create_quote_product_min.yml');
        unset($data['data']['relationships']['product']);
        $data['data']['attributes']['freeFormProduct'] = 'some product';
        $data['data']['attributes']['productSku'] = 'some-product';
        $response = $this->post(
            ['entity' => 'quoteproducts'],
            $data
        );

        $quoteProductId = (int)$this->getResourceId($response);
        /** @var QuoteProduct $quoteProduct */
        $quoteProduct = $this->getEntityManager()->find(QuoteProduct::class, $quoteProductId);
        self::assertTrue(null !== $quoteProduct);
        /** @var QuoteProductOffer $offer */
        $offer = $quoteProduct->getQuoteProductOffers()->first();
        self::assertTrue(null !== $offer);

        $expectedData = $data;
        $expectedData['data']['id'] = (string)$quoteProductId;
        $expectedData['data']['attributes']['freeFormProduct'] = 'some product';
        $expectedData['data']['attributes']['productSku'] = 'some-product';
        $expectedData['data']['attributes']['comment'] = null;
        $expectedData['data']['attributes']['customerComment'] = null;
        $expectedData['data']['relationships']['product'] = ['data' => null];
        $expectedData['data']['relationships']['quoteProductOffers']['data'][0]['id'] = (string)$offer->getId();
        $expectedData['included'][0]['id'] = (string)$offer->getId();
        $this->assertResponseContains($expectedData, $response);
    }

    public function testCreateForFreeFormProductWithoutSku(): void
    {
        $data = $this->getRequestData('create_quote_product_min.yml');
        unset($data['data']['relationships']['product']);
        $data['data']['attributes']['freeFormProduct'] = 'some product';
        $response = $this->post(
            ['entity' => 'quoteproducts'],
            $data
        );

        $quoteProductId = (int)$this->getResourceId($response);
        /** @var QuoteProduct $quoteProduct */
        $quoteProduct = $this->getEntityManager()->find(QuoteProduct::class, $quoteProductId);
        self::assertTrue(null !== $quoteProduct);
        /** @var QuoteProductOffer $offer */
        $offer = $quoteProduct->getQuoteProductOffers()->first();
        self::assertTrue(null !== $offer);

        $expectedData = $data;
        $expectedData['data']['id'] = (string)$quoteProductId;
        $expectedData['data']['attributes']['freeFormProduct'] = 'some product';
        $expectedData['data']['attributes']['productSku'] = null;
        $expectedData['data']['attributes']['comment'] = null;
        $expectedData['data']['attributes']['customerComment'] = null;
        $expectedData['data']['relationships']['product'] = ['data' => null];
        $expectedData['data']['relationships']['quoteProductOffers']['data'][0]['id'] = (string)$offer->getId();
        $expectedData['included'][0]['id'] = (string)$offer->getId();
        $this->assertResponseContains($expectedData, $response);
    }

    public function testTryToCreateForFreeFormProductWithoutName(): void
    {
        $data = $this->getRequestData('create_quote_product_min.yml');
        unset($data['data']['relationships']['product']);
        $data['data']['attributes']['productSku'] = 'some-product';
        $response = $this->post(
            ['entity' => 'quoteproducts'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'quote product constraint',
                'detail' => 'Product cannot be empty.',
                'source' => ['pointer' => '/data/relationships/product/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutQuoteProductOffers(): void
    {
        $data = $this->getRequestData('create_quote_product_min.yml');
        unset($data['data']['relationships']['quoteProductOffers'], $data['included']);
        $response = $this->post(
            ['entity' => 'quoteproducts'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'count constraint',
                'detail' => 'Please add one or more offers.',
                'source' => ['pointer' => '/data/relationships/quoteProductOffers/data']
            ],
            $response
        );
    }

    public function testTryToCreateWithoutProduct(): void
    {
        $data = $this->getRequestData('create_quote_product_min.yml');
        unset($data['data']['relationships']['product']);
        $response = $this->post(
            ['entity' => 'quoteproducts'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'quote product constraint',
                'detail' => 'Product cannot be empty.',
                'source' => ['pointer' => '/data/relationships/product/data']
            ],
            $response
        );
    }

    public function testUpdate(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();

        $response = $this->patch(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId],
            [
                'data' => [
                    'type' => 'quoteproducts',
                    'id' => (string)$quoteProductId,
                    'attributes' => [
                        'comment' => 'some comment'
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'quoteproducts',
                    'id' => (string)$quoteProductId,
                    'attributes' => [
                        'freeFormProduct' => 'product-1.names.default',
                        'productSku' => 'product-1',
                        'comment' => 'some comment',
                        'customerComment' => null
                    ],
                    'relationships' => [
                        'quote' => [
                            'data' => ['type' => 'quotes', 'id' => '<toString(@sale.quote.1->id)>']
                        ],
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product-1->id)>']
                        ],
                        'quoteProductOffers' => [
                            'data' => [
                                [
                                    'type' => 'quoteproductoffers',
                                    'id' => '<toString(@sale.quote.1.product-1.offer.1->id)>'
                                ],
                                [
                                    'type' => 'quoteproductoffers',
                                    'id' => '<toString(@sale.quote.1.product-1.offer.2->id)>'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );

        /** @var QuoteProduct $updatedQuoteProduct */
        $updatedQuoteProduct = $this->getEntityManager()->find(QuoteProduct::class, $quoteProductId);
        self::assertSame('some comment', $updatedQuoteProduct->getComment());
    }

    public function testTryToUpdateQuote(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();
        $oldQuoteId = $this->getReference('sale.quote.1')->getId();

        $response = $this->patch(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId],
            [
                'data' => [
                    'type' => 'quoteproducts',
                    'id' => (string)$quoteProductId,
                    'relationships' => [
                        'quote' => [
                            'data' => ['type' => 'quotes', 'id' => '<toString(@sale.quote.3->id)>']
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'quoteproducts',
                    'id' => (string)$quoteProductId,
                    'relationships' => [
                        'quote' => [
                            'data' => ['type' => 'quotes', 'id' => (string)$oldQuoteId]
                        ]
                    ]
                ]
            ],
            $response
        );

        /** @var QuoteProduct $updatedQuoteProduct */
        $updatedQuoteProduct = $this->getEntityManager()->find(QuoteProduct::class, $quoteProductId);
        self::assertEquals($oldQuoteId, $updatedQuoteProduct->getQuote()->getId());
    }

    public function testUpdateQuoteProductOffers(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();
        $offer1Id = $this->getReference('sale.quote.1.product-1.offer.1')->getId();
        $offer2Id = $this->getReference('sale.quote.1.product-1.offer.2')->getId();

        $this->patch(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId],
            [
                'data' => [
                    'type' => 'quoteproducts',
                    'id' => (string)$quoteProductId,
                    'relationships' => [
                        'quoteProductOffers' => [
                            'data' => [
                                ['type' => 'quoteproductoffers', 'id' => (string)$offer1Id],
                                ['type' => 'quoteproductoffers', 'id' => 'offer_2']
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'quoteproductoffers',
                        'id' => 'offer_2',
                        'attributes' => [
                            'quantity' => 2
                        ],
                        'relationships' => [
                            'productUnit' => [
                                'data' => [
                                    'type' => 'productunits',
                                    'id' => '<toString(@product_unit.box->code)>'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        /** @var QuoteProduct $updatedQuoteProduct */
        $updatedQuoteProduct = $this->getEntityManager()->find(QuoteProduct::class, $quoteProductId);
        self::assertCount(2, $updatedQuoteProduct->getQuoteProductOffers());
        $offer1 = $this->getEntityManager()->find(QuoteProductOffer::class, $offer1Id);
        self::assertFalse(null === $offer1);
        $offer2 = $this->getEntityManager()->find(QuoteProductOffer::class, $offer2Id);
        self::assertTrue(null === $offer2);
    }

    public function testTryToMoveQuoteProductOfferFromAnotherQuoteProduct(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();
        $anotherQuoteProductId = $this->getReference('sale.quote.1.product-2')->getId();

        $response = $this->patch(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId],
            [
                'data' => [
                    'type' => 'quoteproducts',
                    'id' => (string)$quoteProductId,
                    'relationships' => [
                        'quoteProductOffers' => [
                            'data' => [
                                [
                                    'type' => 'quoteproductoffers',
                                    'id' => '<toString(@sale.quote.1.product-2.offer.1->id)>'
                                ],
                                [
                                    'type' => 'quoteproductoffers',
                                    'id' => '<toString(@sale.quote.1.product-1.offer.2->id)>'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'unchangeable field constraint',
                'detail' => 'The offer cannot be moved to another quote product.',
                'source' => ['pointer' => '/data/relationships/quoteProductOffers/data/0']
            ],
            $response
        );

        /** @var QuoteProduct $quoteProduct */
        $quoteProduct = $this->getEntityManager()->find(QuoteProduct::class, $quoteProductId);
        self::assertCount(2, $quoteProduct->getQuoteProductOffers());
        /** @var QuoteProduct $anotherQuoteProduct */
        $anotherQuoteProduct = $this->getEntityManager()->find(QuoteProduct::class, $anotherQuoteProductId);
        self::assertCount(1, $anotherQuoteProduct->getQuoteProductOffers());
    }

    public function testUpdateQuoteProductRequests(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();

        $this->patch(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId],
            [
                'data' => [
                    'type' => 'quoteproducts',
                    'id' => (string)$quoteProductId,
                    'relationships' => [
                        'quoteProductRequests' => [
                            'data' => [
                                [
                                    'type' => 'quoteproductrequests',
                                    'id' => '<toString(@sale.quote.1.product-1.request.1->id)>'
                                ],
                                [
                                    'type' => 'quoteproductrequests',
                                    'id' => 'request_2'
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'quoteproductrequests',
                        'id' => 'request_2',
                        'attributes' => [
                            'quantity' => 2
                        ],
                        'relationships' => [
                            'productUnit' => [
                                'data' => [
                                    'type' => 'productunits',
                                    'id' => '<toString(@product_unit.box->code)>'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        );

        /** @var QuoteProduct $updatedQuoteProduct */
        $updatedQuoteProduct = $this->getEntityManager()->find(QuoteProduct::class, $quoteProductId);
        self::assertCount(2, $updatedQuoteProduct->getQuoteProductRequests());
    }

    public function testUpdateQuoteProductRequestsRemoveAllRequests(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();
        $quoteProductRequestId = $this->getReference('sale.quote.1.product-1.request.1')->getId();

        $this->patch(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId],
            [
                'data' => [
                    'type' => 'quoteproducts',
                    'id' => (string)$quoteProductId,
                    'relationships' => [
                        'quoteProductRequests' => [
                            'data' => []
                        ]
                    ]
                ]
            ]
        );

        /** @var QuoteProduct $updatedQuoteProduct */
        $updatedQuoteProduct = $this->getEntityManager()->find(QuoteProduct::class, $quoteProductId);
        self::assertCount(0, $updatedQuoteProduct->getQuoteProductRequests());
        $quoteProductRequest = $this->getEntityManager()->find(QuoteProductRequest::class, $quoteProductRequestId);
        self::assertTrue(null === $quoteProductRequest);
    }

    public function testTryToMoveQuoteProductRequestFromAnotherQuoteProduct(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();
        $anotherQuoteProductId = $this->getReference('sale.quote.1.product-2')->getId();

        $response = $this->patch(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId],
            [
                'data' => [
                    'type' => 'quoteproducts',
                    'id' => (string)$quoteProductId,
                    'relationships' => [
                        'quoteProductRequests' => [
                            'data' => [
                                [
                                    'type' => 'quoteproductrequests',
                                    'id' => '<toString(@sale.quote.1.product-1.request.1->id)>'
                                ],
                                [
                                    'type' => 'quoteproductrequests',
                                    'id' => '<toString(@sale.quote.1.product-2.request.1->id)>'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'unchangeable field constraint',
                'detail' => 'The request cannot be moved to another quote product.',
                'source' => ['pointer' => '/data/relationships/quoteProductRequests/data/1']
            ],
            $response
        );

        /** @var QuoteProduct $quoteProduct */
        $quoteProduct = $this->getEntityManager()->find(QuoteProduct::class, $quoteProductId);
        self::assertCount(1, $quoteProduct->getQuoteProductRequests());
        /** @var QuoteProduct $anotherQuoteProduct */
        $anotherQuoteProduct = $this->getEntityManager()->find(QuoteProduct::class, $anotherQuoteProductId);
        self::assertCount(1, $anotherQuoteProduct->getQuoteProductRequests());
    }

    public function testDelete(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();

        $this->delete(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId]
        );

        $quoteProduct = $this->getEntityManager()->find(QuoteProduct::class, $quoteProductId);
        self::assertTrue(null === $quoteProduct);
    }

    public function testDeleteList(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();

        $this->cdelete(
            ['entity' => 'quoteproducts'],
            ['filter' => ['id' => (string)$quoteProductId]]
        );

        $quoteProduct = $this->getEntityManager()->find(QuoteProduct::class, $quoteProductId);
        self::assertTrue(null === $quoteProduct);
    }

    public function testGetSubresourceForQuote(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();

        $response = $this->getSubresource(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId, 'association' => 'quote']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'quotes',
                    'id' => '<toString(@sale.quote.1->id)>',
                    'relationships' => [
                        'quoteProducts' => [
                            'data' => [
                                ['type' => 'quoteproducts', 'id' => '<toString(@sale.quote.1.product-1->id)>'],
                                ['type' => 'quoteproducts', 'id' => '<toString(@sale.quote.1.product-2->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForQuote(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();

        $response = $this->getRelationship(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId, 'association' => 'quote']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'quotes', 'id' => '<toString(@sale.quote.1->id)>']],
            $response
        );
    }

    public function testTryToUpdateQuoteViaRelationship(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();

        $response = $this->patchRelationship(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId, 'association' => 'quote'],
            ['data' => ['type' => 'quotes', 'id' => '<toString(@sale.quote.3->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForProduct(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();

        $response = $this->getSubresource(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId, 'association' => 'product']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'products',
                    'id' => '<toString(@product-1->id)>',
                    'attributes' => [
                        'sku' => 'product-1'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForProduct(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();

        $response = $this->getRelationship(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId, 'association' => 'product']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'products', 'id' => '<toString(@product-1->id)>']],
            $response
        );
    }

    public function testUpdateProductViaRelationship(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();
        $productId = $this->getReference('product-2')->getId();

        $this->patchRelationship(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId, 'association' => 'product'],
            ['data' => ['type' => 'products', 'id' => (string)$productId]]
        );

        /** @var QuoteProduct $updatedQuoteProduct */
        $updatedQuoteProduct = $this->getEntityManager()->find(QuoteProduct::class, $quoteProductId);
        self::assertEquals($productId, $updatedQuoteProduct->getProduct()->getId());
        self::assertEquals('product-2.names.default', $updatedQuoteProduct->getFreeFormProduct());
    }

    public function testGetSubresourceForQuoteProductOffers(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();

        $response = $this->getSubresource(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId, 'association' => 'quoteProductOffers']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'quoteproductoffers',
                        'id' => '<toString(@sale.quote.1.product-1.offer.1->id)>',
                        'attributes' => ['value' => '1.0000']
                    ],
                    [
                        'type' => 'quoteproductoffers',
                        'id' => '<toString(@sale.quote.1.product-1.offer.2->id)>',
                        'attributes' => ['value' => '2.0000']
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForQuoteProductOffers(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();

        $response = $this->getRelationship(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId, 'association' => 'quoteProductOffers']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'quoteproductoffers',
                        'id' => '<toString(@sale.quote.1.product-1.offer.1->id)>'
                    ],
                    [
                        'type' => 'quoteproductoffers',
                        'id' => '<toString(@sale.quote.1.product-1.offer.2->id)>'
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateQuoteProductOffersViaRelationship(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();

        $response = $this->patchRelationship(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId, 'association' => 'quoteProductOffers'],
            [
                'data' => [
                    ['type' => 'quoteproductoffers', 'id' => '<toString(@sale.quote.1.product-1.offer.2->id)>']
                ]
            ],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToRemoveQuoteProductOffersViaRelationship(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();

        $response = $this->deleteRelationship(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId, 'association' => 'quoteProductOffers'],
            [
                'data' => [
                    ['type' => 'quoteproductoffers', 'id' => '<toString(@sale.quote.1.product-1.offer.2->id)>']
                ]
            ],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToAddQuoteProductOffersViaRelationship(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();

        $response = $this->postRelationship(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId, 'association' => 'quoteProductOffers'],
            [
                'data' => [
                    ['type' => 'quoteproductoffers', 'id' => '<toString(@sale.quote.1.product-1.offer.2->id)>']
                ]
            ],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForQuoteProductRequests(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();

        $response = $this->getSubresource(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId, 'association' => 'quoteProductRequests']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'quoteproductrequests',
                        'id' => '<toString(@sale.quote.1.product-1.request.1->id)>',
                        'attributes' => ['value' => '1.0000']
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForQuoteProductRequests(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();

        $response = $this->getRelationship(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId, 'association' => 'quoteProductRequests']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'quoteproductrequests',
                        'id' => '<toString(@sale.quote.1.product-1.request.1->id)>'
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateQuoteProductRequestsViaRelationship(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();

        $response = $this->patchRelationship(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId, 'association' => 'quoteProductRequests'],
            [
                'data' => [
                    ['type' => 'quoteproductrequests', 'id' => '<toString(@sale.quote.1.product-1.request.1->id)>']
                ]
            ],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToRemoveQuoteProductRequestsViaRelationship(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();

        $response = $this->deleteRelationship(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId, 'association' => 'quoteProductRequests'],
            [
                'data' => [
                    ['type' => 'quoteproductrequests', 'id' => '<toString(@sale.quote.1.product-1.request.1->id)>']
                ]
            ],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToAddQuoteProductRequestsViaRelationship(): void
    {
        $quoteProductId = $this->getReference('sale.quote.1.product-1')->getId();

        $response = $this->postRelationship(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId, 'association' => 'quoteProductRequests'],
            [
                'data' => [
                    ['type' => 'quoteproductrequests', 'id' => '<toString(@sale.quote.1.product-1.request.1->id)>']
                ]
            ],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
