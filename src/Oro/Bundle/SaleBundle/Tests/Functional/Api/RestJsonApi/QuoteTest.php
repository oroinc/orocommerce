<?php

namespace Oro\Bundle\SameBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
class QuoteTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([LoadQuoteData::class, LoadRequestData::class]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'quotes']);

        $this->assertResponseContains('cget_quote.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'quotes', 'id' => '<toString(@sale.quote.1->id)>']
        );

        $this->assertResponseContains('get_quote.yml', $response);
    }

    public function testCreateWithRequiredDataOnly(): void
    {
        $defaultWebsiteId = $this->getReference('website')->getId();

        $response = $this->post(
            ['entity' => 'quotes'],
            ['data' => ['type' => 'quotes']]
        );

        $quoteId = (int)$this->getResourceId($response);
        /** @var Quote $quote */
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertTrue(null !== $quote);

        $expectedData = $this->getResponseData('create_quote_min.yml');
        $expectedData['data']['id'] = (string)$quoteId;
        $expectedData['data']['attributes']['identifier'] = (string)$quoteId;
        $expectedData['data']['attributes']['guestAccessId'] = $quote->getGuestAccessId();
        $expectedData['data']['attributes']['createdAt'] = $quote->getCreatedAt()->format('Y-m-d\TH:i:s\Z');
        $expectedData['data']['attributes']['updatedAt'] = $quote->getUpdatedAt()->format('Y-m-d\TH:i:s\Z');
        $this->assertResponseContains($expectedData, $response);

        self::assertNotEmpty($quote->getGuestAccessId());
        self::assertEquals($defaultWebsiteId, $quote->getWebsite()->getId());
    }

    public function testCreate(): void
    {
        $defaultWebsiteId = $this->getReference('website')->getId();

        $data = $this->getRequestData('create_quote.yml');
        $response = $this->post(
            ['entity' => 'quotes'],
            $data
        );

        $quoteId = (int)$this->getResourceId($response);
        /** @var Quote $quote */
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertTrue(null !== $quote);
        $shippingAddress = $quote->getShippingAddress();
        self::assertTrue(null !== $shippingAddress);
        self::assertCount(1, $quote->getQuoteProducts());
        /** @var QuoteProduct $quoteProduct */
        $quoteProduct = $quote->getQuoteProducts()->first();
        self::assertCount(1, $quoteProduct->getQuoteProductOffers());
        /** @var QuoteProductOffer $offer */
        $offer = $quoteProduct->getQuoteProductOffers()->first();
        self::assertCount(1, $quoteProduct->getQuoteProductRequests());
        /** @var QuoteProductRequest $request */
        $request = $quoteProduct->getQuoteProductRequests()->first();

        $expectedData = $data;
        $expectedData['data']['id'] = (string)$quoteId;
        $expectedData['data']['attributes']['identifier'] = (string)$quoteId;
        $expectedData['data']['attributes']['guestAccessId'] = $quote->getGuestAccessId();
        $expectedData['data']['attributes']['pricesChanged'] = false;
        $expectedData['data']['attributes']['createdAt'] = $quote->getCreatedAt()->format('Y-m-d\TH:i:s\Z');
        $expectedData['data']['attributes']['updatedAt'] = $quote->getUpdatedAt()->format('Y-m-d\TH:i:s\Z');
        $expectedData['data']['relationships']['customerStatus']['data'] = null;
        $expectedData['data']['relationships']['internalStatus']['data'] = [
            'type' => 'quoteinternalstatuses',
            'id' => 'draft'
        ];
        $expectedData['data']['relationships']['quoteProducts']['data'][0]['id'] = (string)$quoteProduct->getId();
        $expectedData['included'][0]['id'] = (string)$quoteProduct->getId();
        $expectedData['included'][0]['relationships']['quoteProductOffers']['data'][0]['id'] =
            (string)$offer->getId();
        $expectedData['included'][1]['id'] = (string)$offer->getId();
        $expectedData['included'][0]['relationships']['quoteProductRequests']['data'][0]['id'] =
            (string)$request->getId();
        $expectedData['included'][2]['id'] = (string)$request->getId();
        $expectedData['data']['relationships']['shippingAddress']['data']['id'] =
            (string)$shippingAddress->getId();
        $expectedData['included'][3]['id'] = (string)$shippingAddress->getId();
        $this->assertResponseContains($expectedData, $response);

        self::assertNotEmpty($quote->getGuestAccessId());
        self::assertEquals($defaultWebsiteId, $quote->getWebsite()->getId());
    }

    public function testTryToCreateWithShippingAddressFromAnotherQuote(): void
    {
        $response = $this->post(
            ['entity' => 'quotes'],
            [
                'data' => [
                    'type' => 'quotes',
                    'relationships' => [
                        'shippingAddress' => [
                            'data' => [
                                'type' => 'quoteshippingaddresses',
                                'id' => '<toString(@sale.quote.3.shipping_address->id)>'
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
                'title' => 'unchangeable field constraint',
                'detail' => 'This field cannot be changed once set.',
                'source' => ['pointer' => '/data/relationships/shippingAddress/data']
            ],
            $response
        );
    }

    public function testCreateFromRequest(): void
    {
        $data = $this->getRequestData('create_quote.yml');
        $data['data']['relationships']['request']['data'] = [
            'type' => 'rfqs',
            'id' => '<toString(@rfp.request.1->id)>'
        ];
        $data['included'][2]['relationships']['requestProductItem']['data'] = [
            'type' => 'rfqproductitems',
            'id' => '<toString(@rfp.request.1.product_item.1->id)>'
        ];
        $response = $this->post(
            ['entity' => 'quotes'],
            $data
        );

        $quoteId = (int)$this->getResourceId($response);
        /** @var Quote $quote */
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertTrue(null !== $quote);
        $shippingAddress = $quote->getShippingAddress();
        self::assertTrue(null !== $shippingAddress);
        self::assertCount(1, $quote->getQuoteProducts());
        /** @var QuoteProduct $quoteProduct */
        $quoteProduct = $quote->getQuoteProducts()->first();
        self::assertCount(1, $quoteProduct->getQuoteProductOffers());
        /** @var QuoteProductOffer $offer */
        $offer = $quoteProduct->getQuoteProductOffers()->first();
        self::assertCount(1, $quoteProduct->getQuoteProductRequests());
        /** @var QuoteProductRequest $request */
        $request = $quoteProduct->getQuoteProductRequests()->first();

        $expectedData = $data;
        $expectedData['data']['id'] = (string)$quoteId;
        $expectedData['data']['attributes']['identifier'] = (string)$quoteId;
        $expectedData['data']['attributes']['guestAccessId'] = $quote->getGuestAccessId();
        $expectedData['data']['attributes']['pricesChanged'] = false;
        $expectedData['data']['attributes']['createdAt'] = $quote->getCreatedAt()->format('Y-m-d\TH:i:s\Z');
        $expectedData['data']['attributes']['updatedAt'] = $quote->getUpdatedAt()->format('Y-m-d\TH:i:s\Z');
        $expectedData['data']['relationships']['customerStatus']['data'] = null;
        $expectedData['data']['relationships']['internalStatus']['data'] = [
            'type' => 'quoteinternalstatuses',
            'id' => 'draft'
        ];
        $expectedData['data']['relationships']['quoteProducts']['data'][0]['id'] = (string)$quoteProduct->getId();
        $expectedData['included'][0]['id'] = (string)$quoteProduct->getId();
        $expectedData['included'][0]['relationships']['quoteProductOffers']['data'][0]['id'] =
            (string)$offer->getId();
        $expectedData['included'][1]['id'] = (string)$offer->getId();
        $expectedData['included'][0]['relationships']['quoteProductRequests']['data'][0]['id'] =
            (string)$request->getId();
        $expectedData['included'][2]['id'] = (string)$request->getId();
        $expectedData['data']['relationships']['shippingAddress']['data']['id'] =
            (string)$shippingAddress->getId();
        $expectedData['included'][3]['id'] = (string)$shippingAddress->getId();
        $this->assertResponseContains($expectedData, $response);
    }

    public function testTryToCreateWhenQuoteProductRequestSourceIsInvalid(): void
    {
        $data = $this->getRequestData('create_quote.yml');
        $data['data']['relationships']['request']['data'] = [
            'type' => 'rfqs',
            'id' => '<toString(@rfp.request.1->id)>'
        ];
        $data['included'][2]['relationships']['requestProductItem']['data'] = [
            'type' => 'rfqproductitems',
            'id' => '<toString(@rfp.request.2.product_item.1->id)>'
        ];
        $response = $this->post(
            ['entity' => 'quotes'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'quote product request source constraint',
                'detail' => 'The request can be created from the same RFQ as the quote.',
                'source' => ['pointer' => '/included/2/relationships/requestProductItem/data']
            ],
            $response
        );
    }

    public function testTryToCreateWhenQuoteProductRequestHasSourceButQuoteDoesNotHaveSourceRequest(): void
    {
        $data = $this->getRequestData('create_quote.yml');
        $data['included'][2]['relationships']['requestProductItem']['data'] = [
            'type' => 'rfqproductitems',
            'id' => '<toString(@rfp.request.1.product_item.1->id)>'
        ];
        $response = $this->post(
            ['entity' => 'quotes'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'quote product request source constraint',
                'detail' => 'The request can be created from the same RFQ as the quote.',
                'source' => ['pointer' => '/included/2/relationships/requestProductItem/data']
            ],
            $response
        );
    }

    public function testTryToChangeShippingAddress(): void
    {
        $quoteId = $this->getReference('sale.quote.1')->getId();

        $response = $this->patch(
            ['entity' => 'quotes', 'id' => (string)$quoteId],
            [
                'data' => [
                    'type' => 'quotes',
                    'id' => (string)$quoteId,
                    'relationships' => [
                        'shippingAddress' => [
                            'data' => [
                                'type' => 'quoteshippingaddresses',
                                'id' => 'new_shipping_address'
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'quoteshippingaddresses',
                        'id' => 'new_shipping_address',
                        'relationships' => [
                            'country' => [
                                'data' => ['type' => 'countries', 'id' => '<toString(@country.US->iso2Code)>']
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
                'title' => 'unchangeable field constraint',
                'detail' => 'This field cannot be changed once set.',
                'source' => ['pointer' => '/data/relationships/shippingAddress/data']
            ],
            $response
        );
    }

    public function testTryToUseShippingAddressFromAnotherQuote(): void
    {
        $quoteId = $this->getReference('sale.quote.1')->getId();

        $response = $this->patch(
            ['entity' => 'quotes', 'id' => (string)$quoteId],
            [
                'data' => [
                    'type' => 'quotes',
                    'id' => (string)$quoteId,
                    'relationships' => [
                        'shippingAddress' => [
                            'data' => [
                                'type' => 'quoteshippingaddresses',
                                'id' => '<toString(@sale.quote.3.shipping_address->id)>'
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
                'title' => 'unchangeable field constraint',
                'detail' => 'This field cannot be changed once set.',
                'source' => ['pointer' => '/data/relationships/shippingAddress/data']
            ],
            $response
        );
    }

    public function testTryToUseShippingAddressFromAnotherQuoteWhenUpdatedQuoteHasNoShippingAddress(): void
    {
        $quoteId = $this->getReference('sale.quote.2')->getId();

        $response = $this->patch(
            ['entity' => 'quotes', 'id' => (string)$quoteId],
            [
                'data' => [
                    'type' => 'quotes',
                    'id' => (string)$quoteId,
                    'relationships' => [
                        'shippingAddress' => [
                            'data' => [
                                'type' => 'quoteshippingaddresses',
                                'id' => '<toString(@sale.quote.3.shipping_address->id)>'
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
                'title' => 'unchangeable field constraint',
                'detail' => 'This field cannot be changed once set.',
                'source' => ['pointer' => '/data/relationships/shippingAddress/data']
            ],
            $response
        );
    }

    public function testUpdateShippingAddressWhenItIsNotChanged(): void
    {
        $quoteId = $this->getReference('sale.quote.1')->getId();

        $data = [
            'data' => [
                'type' => 'quotes',
                'id' => (string)$quoteId,
                'relationships' => [
                    'shippingAddress' => [
                        'data' => [
                            'type' => 'quoteshippingaddresses',
                            'id' => '<toString(@sale.quote.1.shipping_address->id)>'
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'quotes', 'id' => (string)$quoteId],
            $data
        );

        $this->assertResponseContains($data, $response);
    }

    public function testUpdateShippingAddressToNull(): void
    {
        $quoteId = $this->getReference('sale.quote.1')->getId();
        $addressId = $this->getReference('sale.quote.1.shipping_address')->getId();

        $data = [
            'data' => [
                'type' => 'quotes',
                'id' => (string)$quoteId,
                'relationships' => [
                    'shippingAddress' => [
                        'data' => null
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'quotes', 'id' => (string)$quoteId],
            $data
        );

        $this->assertResponseContains($data, $response);

        /** @var Quote $quote */
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertTrue(null === $quote->getShippingAddress());
        $address = $this->getEntityManager()->find(QuoteAddress::class, $addressId);
        self::assertTrue(null === $address);
    }

    public function testDelete(): void
    {
        /** @var Quote $quote */
        $quote = $this->getReference('sale.quote.1');

        $entitiesToBeDeleted = [];
        $entitiesToBeDeleted[] = [Quote::class, $quote->getId()];
        $entitiesToBeDeleted[] = [QuoteAddress::class, $quote->getShippingAddress()->getId()];
        foreach ($quote->getQuoteProducts() as $quoteProduct) {
            $entitiesToBeDeleted[] = [QuoteProduct::class, $quoteProduct->getId()];
            foreach ($quoteProduct->getQuoteProductOffers() as $offer) {
                $entitiesToBeDeleted[] = [QuoteProductOffer::class, $offer->getId()];
            }
        }

        $this->delete(
            ['entity' => 'quotes', 'id' => (string)$quote->getId()]
        );

        foreach ($entitiesToBeDeleted as [$entityClass, $entityId]) {
            $entity = $this->getEntityManager()->find($entityClass, $entityId);
            self::assertTrue(null === $entity, $entityClass);
        }
    }

    public function testDeleteList(): void
    {
        /** @var Quote $quote */
        $quote = $this->getReference('sale.quote.1');

        $entitiesToBeDeleted = [];
        $entitiesToBeDeleted[] = [Quote::class, $quote->getId()];
        $entitiesToBeDeleted[] = [QuoteAddress::class, $quote->getShippingAddress()->getId()];
        foreach ($quote->getQuoteProducts() as $quoteProduct) {
            $entitiesToBeDeleted[] = [QuoteProduct::class, $quoteProduct->getId()];
            foreach ($quoteProduct->getQuoteProductOffers() as $offer) {
                $entitiesToBeDeleted[] = [QuoteProductOffer::class, $offer->getId()];
            }
        }

        $this->cdelete(
            ['entity' => 'quotes'],
            ['filter' => ['id' => (string)$quote->getId()]]
        );

        foreach ($entitiesToBeDeleted as [$entityClass, $entityId]) {
            $entity = $this->getEntityManager()->find($entityClass, $entityId);
            self::assertTrue(null === $entity, $entityClass);
        }
    }

    public function testGetSubresourceForShippingAddress(): void
    {
        $quoteId = $this->getReference('sale.quote.1')->getId();

        $response = $this->getSubresource(
            ['entity' => 'quotes', 'id' => (string)$quoteId, 'association' => 'shippingAddress'],
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'quoteshippingaddresses',
                    'id' => '<toString(@sale.quote.1.shipping_address->id)>',
                    'relationships' => [
                        'country' => [
                            'data' => ['type' => 'countries', 'id' => '<toString(@country.US->iso2Code)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForShippingAddress(): void
    {
        $quoteId = $this->getReference('sale.quote.1')->getId();

        $response = $this->getRelationship(
            ['entity' => 'quotes', 'id' => (string)$quoteId, 'association' => 'shippingAddress'],
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'quoteshippingaddresses', 'id' => '<toString(@sale.quote.1.shipping_address->id)>']],
            $response
        );
    }

    public function testTryToUpdateShippingAddressViaRelationship(): void
    {
        $quoteId = $this->getReference('sale.quote.1')->getId();

        $response = $this->patchRelationship(
            ['entity' => 'quotes', 'id' => (string)$quoteId, 'association' => 'shippingAddress'],
            [
                'data' => [
                    'type' => 'quoteshippingaddresses',
                    'id' => '<toString(@sale.quote.3.shipping_address->id)>'
                ]
            ],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForCustomerUser(): void
    {
        $quoteId = $this->getReference('sale.quote.3')->getId();

        $response = $this->getSubresource(
            ['entity' => 'quotes', 'id' => (string)$quoteId, 'association' => 'customerUser'],
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'customerusers',
                    'id' => '<toString(@sale-customer1-user1@example.com->id)>',
                    'attributes' => [
                        'email' => 'sale-customer1-user1@example.com'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForCustomerUser(): void
    {
        $quoteId = $this->getReference('sale.quote.3')->getId();

        $response = $this->getRelationship(
            ['entity' => 'quotes', 'id' => (string)$quoteId, 'association' => 'customerUser'],
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'customerusers', 'id' => '<toString(@sale-customer1-user1@example.com->id)>']],
            $response
        );
    }

    public function testUpdateCustomerUserViaRelationship(): void
    {
        $quoteId = $this->getReference('sale.quote.3')->getId();
        $customerUserId = $this->getReference('sale-customer1-user2@example.com')->getId();

        $this->patchRelationship(
            ['entity' => 'quotes', 'id' => (string)$quoteId, 'association' => 'customerUser'],
            [
                'data' => [
                    'type' => 'customerusers',
                    'id' => (string)$customerUserId
                ]
            ]
        );

        /** @var Quote $quote */
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertEquals($customerUserId, $quote->getCustomerUser()->getId());
    }

    public function testGetSubresourceForAssignedCustomerUsers(): void
    {
        $quoteId = $this->getReference('sale.quote.3')->getId();

        $response = $this->getSubresource(
            ['entity' => 'quotes', 'id' => (string)$quoteId, 'association' => 'assignedCustomerUsers'],
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'customerusers',
                        'id' => '<toString(@sale-customer1-user1@example.com->id)>',
                        'attributes' => [
                            'email' => 'sale-customer1-user1@example.com'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForAssignedCustomerUsers(): void
    {
        $quoteId = $this->getReference('sale.quote.3')->getId();

        $response = $this->getRelationship(
            ['entity' => 'quotes', 'id' => (string)$quoteId, 'association' => 'assignedCustomerUsers'],
        );

        $this->assertResponseContains(
            ['data' => [['type' => 'customerusers', 'id' => '<toString(@sale-customer1-user1@example.com->id)>']]],
            $response
        );
    }

    public function testUpdateAssignedCustomerUsersViaRelationship(): void
    {
        $quoteId = $this->getReference('sale.quote.3')->getId();

        $this->patchRelationship(
            ['entity' => 'quotes', 'id' => (string)$quoteId, 'association' => 'assignedCustomerUsers'],
            [
                'data' => [
                    ['type' => 'customerusers', 'id' => '<toString(@sale-customer1-user1@example.com->id)>'],
                    ['type' => 'customerusers', 'id' => '<toString(@sale-customer1-user2@example.com->id)>']
                ]
            ]
        );

        /** @var Quote $quote */
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertCount(2, $quote->getAssignedCustomerUsers());
    }

    public function testRemoveAssignedCustomerUsersViaRelationship(): void
    {
        $quoteId = $this->getReference('sale.quote.3')->getId();

        $this->deleteRelationship(
            ['entity' => 'quotes', 'id' => (string)$quoteId, 'association' => 'assignedCustomerUsers'],
            [
                'data' => [
                    ['type' => 'customerusers', 'id' => '<toString(@sale-customer1-user1@example.com->id)>']
                ]
            ]
        );

        /** @var Quote $quote */
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertCount(0, $quote->getAssignedCustomerUsers());
    }

    public function testAddAssignedCustomerUsersViaRelationship(): void
    {
        $quoteId = $this->getReference('sale.quote.3')->getId();

        $this->postRelationship(
            ['entity' => 'quotes', 'id' => (string)$quoteId, 'association' => 'assignedCustomerUsers'],
            [
                'data' => [
                    ['type' => 'customerusers', 'id' => '<toString(@sale-customer1-user2@example.com->id)>']
                ]
            ]
        );

        /** @var Quote $quote */
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertCount(2, $quote->getAssignedCustomerUsers());
    }

    public function testGetSubresourceForAssignedUsers(): void
    {
        $quoteId = $this->getReference('sale.quote.3')->getId();

        $response = $this->getSubresource(
            ['entity' => 'quotes', 'id' => (string)$quoteId, 'association' => 'assignedUsers'],
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'users',
                        'id' => '<toString(@sale-user1->id)>',
                        'attributes' => [
                            'email' => 'sale-user1@example.com'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForAssignedUsers(): void
    {
        $quoteId = $this->getReference('sale.quote.3')->getId();

        $response = $this->getRelationship(
            ['entity' => 'quotes', 'id' => (string)$quoteId, 'association' => 'assignedUsers'],
        );

        $this->assertResponseContains(
            ['data' => [['type' => 'users', 'id' => '<toString(@sale-user1->id)>']]],
            $response
        );
    }

    public function testUpdateAssignedUsersViaRelationship(): void
    {
        $quoteId = $this->getReference('sale.quote.3')->getId();

        $this->patchRelationship(
            ['entity' => 'quotes', 'id' => (string)$quoteId, 'association' => 'assignedUsers'],
            [
                'data' => [
                    ['type' => 'users', 'id' => '<toString(@sale-user1->id)>'],
                    ['type' => 'users', 'id' => '<toString(@sale-user2->id)>']
                ]
            ]
        );

        /** @var Quote $quote */
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertCount(2, $quote->getAssignedUsers());
    }

    public function testRemoveAssignedUsersViaRelationship(): void
    {
        $quoteId = $this->getReference('sale.quote.3')->getId();

        $this->deleteRelationship(
            ['entity' => 'quotes', 'id' => (string)$quoteId, 'association' => 'assignedUsers'],
            [
                'data' => [
                    ['type' => 'users', 'id' => '<toString(@sale-user1->id)>']
                ]
            ]
        );

        /** @var Quote $quote */
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertCount(0, $quote->getAssignedUsers());
    }

    public function testAddAssignedUsersViaRelationship(): void
    {
        $quoteId = $this->getReference('sale.quote.3')->getId();

        $this->postRelationship(
            ['entity' => 'quotes', 'id' => (string)$quoteId, 'association' => 'assignedUsers'],
            [
                'data' => [
                    ['type' => 'users', 'id' => '<toString(@sale-user2->id)>']
                ]
            ]
        );

        /** @var Quote $quote */
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertCount(2, $quote->getAssignedUsers());
    }
}
