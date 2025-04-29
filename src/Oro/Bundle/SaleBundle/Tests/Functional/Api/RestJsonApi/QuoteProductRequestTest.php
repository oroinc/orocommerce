<?php

namespace Oro\Bundle\SameBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QuoteProductRequestTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([LoadQuoteData::class]);
    }

    private function generateQuoteProductRequestChecksum(QuoteProductRequest $request): string
    {
        /** @var LineItemChecksumGeneratorInterface $checksumGenerator */
        $checksumGenerator = self::getContainer()->get('oro_product.line_item_checksum_generator');
        $checksum = $checksumGenerator->getChecksum($request);
        self::assertNotEmpty($checksum, 'Impossible to generate the quote product request checksum.');

        return $checksum;
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'quoteproductrequests']);

        $this->assertResponseContains('cget_quote_product_request.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'quoteproductrequests', 'id' => '<toString(@sale.quote.1.product-1.request.1->id)>']
        );

        $this->assertResponseContains('get_quote_product_request.yml', $response);
    }

    public function testCreateWithRequiredDataOnly(): void
    {
        $data = $this->getRequestData('create_quote_product_request_min.yml');
        $response = $this->post(
            ['entity' => 'quoteproductrequests'],
            $data
        );

        $requestId = (int)$this->getResourceId($response);
        /** @var QuoteProductRequest $request */
        $request = $this->getEntityManager()->find(QuoteProductRequest::class, $requestId);
        self::assertTrue(null !== $request);

        $expectedData = $data;
        $expectedData['data']['id'] = (string)$requestId;
        $expectedData['data']['attributes']['productUnitCode'] = 'box';
        $expectedData['data']['attributes']['value'] = null;
        $expectedData['data']['attributes']['currency'] = null;
        $expectedData['data']['attributes']['checksum'] = $this->generateQuoteProductRequestChecksum($request);
        $this->assertResponseContains($expectedData, $response);

        self::assertNull($request->getPrice());
    }

    public function testCreate(): void
    {
        $data = $this->getRequestData('create_quote_product_request_min.yml');
        $data['data']['attributes']['value'] = '1.99';
        $data['data']['attributes']['currency'] = 'USD';
        $data['data']['attributes']['quantity'] = 5;
        $response = $this->post(
            ['entity' => 'quoteproductrequests'],
            $data
        );

        $requestId = (int)$this->getResourceId($response);
        /** @var QuoteProductRequest $request */
        $request = $this->getEntityManager()->find(QuoteProductRequest::class, $requestId);
        self::assertTrue(null !== $request);

        $expectedData = $data;
        $expectedData['data']['id'] = (string)$requestId;
        $expectedData['data']['attributes']['value'] = '1.9900';
        $expectedData['data']['attributes']['checksum'] = $this->generateQuoteProductRequestChecksum($request);
        $this->assertResponseContains($expectedData, $response);

        self::assertNotNull($request->getPrice());
        self::assertSame('1.9900', $request->getPrice()->getValue());
        self::assertEquals('USD', $request->getPrice()->getCurrency());
        self::assertSame(5.0, $request->getQuantity());
    }

    public function testTryToCreateWithCurrencyButWithoutValue(): void
    {
        $data = $this->getRequestData('create_quote_product_request_min.yml');
        $data['data']['attributes']['currency'] = 'USD';
        $response = $this->post(
            ['entity' => 'quoteproductrequests'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'Price value should not be blank.',
                'source' => ['pointer' => '/data/attributes/value']
            ],
            $response
        );
    }

    public function testTryToCreateWithValueButWithoutCurrency(): void
    {
        $data = $this->getRequestData('create_quote_product_request_min.yml');
        $data['data']['attributes']['value'] = '1.99';
        $response = $this->post(
            ['entity' => 'quoteproductrequests'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/currency']
            ],
            $response
        );
    }

    public function testTryToCreateWithEmptyValue(): void
    {
        $data = $this->getRequestData('create_quote_product_request_min.yml');
        $data['data']['attributes']['value'] = '';
        $response = $this->post(
            ['entity' => 'quoteproductrequests'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'Price value should not be blank.',
                'source' => ['pointer' => '/data/attributes/value']
            ],
            $response
        );
    }

    public function testTryToCreateWithEmptyCurrency(): void
    {
        $data = $this->getRequestData('create_quote_product_request_min.yml');
        $data['data']['attributes']['currency'] = '';
        $response = $this->post(
            ['entity' => 'quoteproductrequests'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/currency']
            ],
            $response
        );
    }

    public function testTryToCreateWithWrongValue(): void
    {
        $data = $this->getRequestData('create_quote_product_request_min.yml');
        $data['data']['attributes']['value'] = 'test';
        $response = $this->post(
            ['entity' => 'quoteproductrequests'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title' => 'type constraint',
                'detail' => 'This value should be of type numeric.',
                'source' => ['pointer' => '/data/attributes/value']
            ],
            $response
        );
    }

    public function testCreateWithReadonlyChecksum(): void
    {
        $data = $this->getRequestData('create_quote_product_request_min.yml');
        $data['data']['attributes']['checksum'] = '123456789';
        $response = $this->post(['entity' => 'quoteproductrequests'], $data);

        $requestId = $this->getResourceId($response);
        /** @var QuoteProductRequest $request */
        $request = $this->getEntityManager()->find(QuoteProductRequest::class, $requestId);
        $expectedChecksum = $this->generateQuoteProductRequestChecksum($request);
        $expectedData = $data;
        $expectedData['data']['id'] = $requestId;
        $expectedData['data']['attributes']['checksum'] = $expectedChecksum;
        $this->assertResponseContains($expectedData, $response);
        self::assertEquals($expectedChecksum, $request->getChecksum());
    }

    public function testUpdate(): void
    {
        $requestId = $this->getReference('sale.quote.1.product-1.request.1')->getId();
        $productUnitCode = $this->getReference('product_unit.bottle')->getCode();

        $response = $this->patch(
            ['entity' => 'quoteproductrequests', 'id' => (string)$requestId],
            [
                'data' => [
                    'type' => 'quoteproductrequests',
                    'id' => (string)$requestId,
                    'attributes' => [
                        'quantity' => 10
                    ],
                    'relationships' => [
                        'productUnit' => [
                            'data' => ['type' => 'productunits', 'id' => $productUnitCode]
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'quoteproductrequests',
                    'id' => (string)$requestId,
                    'attributes' => [
                        'quantity' => 10,
                        'value' => '1.0000',
                        'currency' => 'USD'
                    ],
                    'relationships' => [
                        'quoteProduct' => [
                            'data' => ['type' => 'quoteproducts', 'id' => '<toString(@sale.quote.1.product-1->id)>']
                        ],
                        'productUnit' => [
                            'data' => ['type' => 'productunits', 'id' => $productUnitCode]
                        ]
                    ]
                ]
            ],
            $response
        );

        /** @var QuoteProductRequest $updatedRequest */
        $updatedRequest = $this->getEntityManager()->find(QuoteProductRequest::class, $requestId);
        self::assertSame(10.0, $updatedRequest->getQuantity());
        self::assertEquals($productUnitCode, $updatedRequest->getProductUnitCode());
        self::assertEquals($productUnitCode, $updatedRequest->getProductUnit()->getCode());
    }

    public function testTryToUpdateQuoteProduct(): void
    {
        $requestId = $this->getReference('sale.quote.1.product-1.request.1')->getId();
        $oldQuoteProductId = $this->getReference('sale.quote.1.product-1')->getId();

        $response = $this->patch(
            ['entity' => 'quoteproductrequests', 'id' => (string)$requestId],
            [
                'data' => [
                    'type' => 'quoteproductrequests',
                    'id' => (string)$requestId,
                    'relationships' => [
                        'quoteProduct' => [
                            'data' => ['type' => 'quoteproducts', 'id' => '<toString(@sale.quote.1.product-2->id)>']
                        ]
                    ]
                ]
            ]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'quoteproductrequests',
                    'id' => (string)$requestId,
                    'relationships' => [
                        'quoteProduct' => [
                            'data' => ['type' => 'quoteproducts', 'id' => (string)$oldQuoteProductId]
                        ]
                    ]
                ]
            ],
            $response
        );

        /** @var QuoteProductRequest $updatedRequest */
        $updatedRequest = $this->getEntityManager()->find(QuoteProductRequest::class, $requestId);
        self::assertEquals($oldQuoteProductId, $updatedRequest->getQuoteProduct()->getId());
    }

    public function testTryToUpdateReadonlyChecksum(): void
    {
        $requestId = $this->getReference('sale.quote.1.product-1.request.1')->getId();

        $data = [
            'data' => [
                'type' => 'quoteproductrequests',
                'id' => (string)$requestId,
                'attributes' => [
                    'checksum' => '123456789'
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'quoteproductrequests', 'id' => (string)$requestId],
            $data
        );

        /** @var QuoteProductRequest $updatedRequest */
        $updatedRequest = $this->getEntityManager()->find(QuoteProductRequest::class, $requestId);
        $expectedChecksum = $this->generateQuoteProductRequestChecksum($updatedRequest);
        $expectedData = $data;
        $expectedData['data']['attributes']['checksum'] = $expectedChecksum;
        $this->assertResponseContains($expectedData, $response);
        self::assertEquals($expectedChecksum, $updatedRequest->getChecksum());
    }

    public function testDelete(): void
    {
        $requestId = $this->getReference('sale.quote.1.product-1.request.1')->getId();

        $this->delete(
            ['entity' => 'quoteproductrequests', 'id' => (string)$requestId]
        );

        $request = $this->getEntityManager()->find(QuoteProductRequest::class, $requestId);
        self::assertTrue(null === $request);
    }

    public function testDeleteList(): void
    {
        $requestId = $this->getReference('sale.quote.1.product-1.request.1')->getId();

        $this->cdelete(
            ['entity' => 'quoteproductrequests'],
            ['filter' => ['id' => (string)$requestId]]
        );

        $request = $this->getEntityManager()->find(QuoteProductRequest::class, $requestId);
        self::assertTrue(null === $request);
    }

    public function testGetSubresourceForQuoteProduct(): void
    {
        $requestId = $this->getReference('sale.quote.1.product-1.request.1')->getId();

        $response = $this->getSubresource(
            ['entity' => 'quoteproductrequests', 'id' => (string)$requestId, 'association' => 'quoteProduct']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'quoteproducts',
                    'id' => '<toString(@sale.quote.1.product-1->id)>',
                    'relationships' => [
                        'product' => [
                            'data' => ['type' => 'products', 'id' => '<toString(@product-1->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForQuoteProduct(): void
    {
        $requestId = $this->getReference('sale.quote.1.product-1.request.1')->getId();

        $response = $this->getRelationship(
            ['entity' => 'quoteproductrequests', 'id' => (string)$requestId, 'association' => 'quoteProduct']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'quoteproducts', 'id' => '<toString(@sale.quote.1.product-1->id)>']],
            $response
        );
    }

    public function testTryToUpdateQuoteProductViaRelationship(): void
    {
        $requestId = $this->getReference('sale.quote.1.product-1.request.1')->getId();

        $response = $this->patchRelationship(
            ['entity' => 'quoteproductrequests', 'id' => (string)$requestId, 'association' => 'quoteProduct'],
            ['data' => ['type' => 'quoteproducts', 'id' => '<toString(@sale.quote.1.product-2->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForProductUnit(): void
    {
        $requestId = $this->getReference('sale.quote.1.product-1.request.1')->getId();

        $response = $this->getSubresource(
            ['entity' => 'quoteproductrequests', 'id' => (string)$requestId, 'association' => 'productUnit']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'productunits',
                    'id' => '<toString(@product_unit.liter->code)>',
                    'attributes' => [
                        'label' => 'liter'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForProductUnit(): void
    {
        $requestId = $this->getReference('sale.quote.1.product-1.request.1')->getId();

        $response = $this->getRelationship(
            ['entity' => 'quoteproductrequests', 'id' => (string)$requestId, 'association' => 'productUnit']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.liter->code)>']],
            $response
        );
    }

    public function testUpdateProductUnitViaRelationship(): void
    {
        $requestId = $this->getReference('sale.quote.1.product-1.request.1')->getId();
        $productUnitCode = $this->getReference('product_unit.bottle')->getCode();

        $this->patchRelationship(
            ['entity' => 'quoteproductrequests', 'id' => (string)$requestId, 'association' => 'productUnit'],
            ['data' => ['type' => 'productunits', 'id' => $productUnitCode]]
        );

        /** @var QuoteProductRequest $updatedRequest */
        $updatedRequest = $this->getEntityManager()->find(QuoteProductRequest::class, $requestId);
        self::assertEquals($productUnitCode, $updatedRequest->getProductUnit()->getCode());
        self::assertEquals($productUnitCode, $updatedRequest->getProductUnitCode());
    }

    public function testGetSubresourceForRequestProductItem(): void
    {
        $requestId = $this->getReference('sale.quote.1.product-1.request.1')->getId();

        $response = $this->getSubresource(
            ['entity' => 'quoteproductrequests', 'id' => (string)$requestId, 'association' => 'requestProductItem']
        );

        $this->assertResponseContains(
            ['data' => null],
            $response
        );
    }

    public function testGetRelationshipForRequestProductItem(): void
    {
        $requestId = $this->getReference('sale.quote.1.product-1.request.1')->getId();

        $response = $this->getRelationship(
            ['entity' => 'quoteproductrequests', 'id' => (string)$requestId, 'association' => 'requestProductItem']
        );

        $this->assertResponseContains(
            ['data' => null],
            $response
        );
    }

    public function testTryToUpdateRequestProductItemViaRelationship(): void
    {
        $requestId = $this->getReference('sale.quote.1.product-1.request.1')->getId();

        $response = $this->patchRelationship(
            ['entity' => 'quoteproductrequests', 'id' => (string)$requestId, 'association' => 'requestProductItem'],
            ['data' => null],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
