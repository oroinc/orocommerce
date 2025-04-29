<?php

namespace Oro\Bundle\SameBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QuoteProductOfferTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([LoadQuoteData::class]);
    }

    private function generateQuoteProductOfferChecksum(QuoteProductOffer $offer): string
    {
        /** @var LineItemChecksumGeneratorInterface $checksumGenerator */
        $checksumGenerator = self::getContainer()->get('oro_product.line_item_checksum_generator');
        $checksum = $checksumGenerator->getChecksum($offer);
        self::assertNotEmpty($checksum, 'Impossible to generate the quote product offer checksum.');

        return $checksum;
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => 'quoteproductoffers']);

        $this->assertResponseContains('cget_quote_product_offer.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'quoteproductoffers', 'id' => '<toString(@sale.quote.1.product-1.offer.1->id)>']
        );

        $this->assertResponseContains('get_quote_product_offer.yml', $response);
    }

    public function testCreateWithRequiredDataOnly(): void
    {
        $data = $this->getRequestData('create_quote_product_offer_min.yml');
        $response = $this->post(
            ['entity' => 'quoteproductoffers'],
            $data
        );

        $offerId = (int)$this->getResourceId($response);
        /** @var QuoteProductOffer $offer */
        $offer = $this->getEntityManager()->find(QuoteProductOffer::class, $offerId);
        self::assertTrue(null !== $offer);

        $expectedData = $data;
        $expectedData['data']['id'] = (string)$offerId;
        $expectedData['data']['attributes']['allowIncrements'] = false;
        $expectedData['data']['attributes']['productUnitCode'] = 'box';
        $expectedData['data']['attributes']['value'] = null;
        $expectedData['data']['attributes']['currency'] = null;
        $expectedData['data']['attributes']['checksum'] = $this->generateQuoteProductOfferChecksum($offer);
        $this->assertResponseContains($expectedData, $response);

        self::assertFalse($offer->isAllowIncrements());
        self::assertNull($offer->getPrice());
    }

    public function testCreate(): void
    {
        $data = $this->getRequestData('create_quote_product_offer_min.yml');
        $data['data']['attributes']['allowIncrements'] = true;
        $data['data']['attributes']['value'] = '1.99';
        $data['data']['attributes']['currency'] = 'USD';
        $data['data']['attributes']['quantity'] = 5;
        $response = $this->post(
            ['entity' => 'quoteproductoffers'],
            $data
        );

        $offerId = (int)$this->getResourceId($response);
        /** @var QuoteProductOffer $offer */
        $offer = $this->getEntityManager()->find(QuoteProductOffer::class, $offerId);
        self::assertTrue(null !== $offer);

        $expectedData = $data;
        $expectedData['data']['id'] = (string)$offerId;
        $expectedData['data']['attributes']['value'] = '1.9900';
        $expectedData['data']['attributes']['checksum'] = $this->generateQuoteProductOfferChecksum($offer);
        $this->assertResponseContains($expectedData, $response);

        self::assertTrue($offer->isAllowIncrements());
        self::assertNotNull($offer->getPrice());
        self::assertSame('1.9900', $offer->getPrice()->getValue());
        self::assertEquals('USD', $offer->getPrice()->getCurrency());
        self::assertSame(5.0, $offer->getQuantity());
    }

    public function testTryToCreateWithoutQuantity(): void
    {
        $data = $this->getRequestData('create_quote_product_offer_min.yml');
        unset($data['data']['attributes']);
        $response = $this->post(
            ['entity' => 'quoteproductoffers'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/attributes/quantity']
            ],
            $response
        );
    }

    public function testTryToCreateWithCurrencyButWithoutValue(): void
    {
        $data = $this->getRequestData('create_quote_product_offer_min.yml');
        $data['data']['attributes']['currency'] = 'USD';
        $response = $this->post(
            ['entity' => 'quoteproductoffers'],
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
        $data = $this->getRequestData('create_quote_product_offer_min.yml');
        $data['data']['attributes']['value'] = '1.99';
        $response = $this->post(
            ['entity' => 'quoteproductoffers'],
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
        $data = $this->getRequestData('create_quote_product_offer_min.yml');
        $data['data']['attributes']['value'] = '';
        $response = $this->post(
            ['entity' => 'quoteproductoffers'],
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
        $data = $this->getRequestData('create_quote_product_offer_min.yml');
        $data['data']['attributes']['currency'] = '';
        $response = $this->post(
            ['entity' => 'quoteproductoffers'],
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
        $data = $this->getRequestData('create_quote_product_offer_min.yml');
        $data['data']['attributes']['value'] = 'test';
        $response = $this->post(
            ['entity' => 'quoteproductoffers'],
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
        $data = $this->getRequestData('create_quote_product_offer_min.yml');
        $data['data']['attributes']['checksum'] = '123456789';
        $response = $this->post(['entity' => 'quoteproductoffers'], $data);

        $offerId = $this->getResourceId($response);
        /** @var QuoteProductOffer $offer */
        $offer = $this->getEntityManager()->find(QuoteProductOffer::class, $offerId);
        $expectedChecksum = $this->generateQuoteProductOfferChecksum($offer);
        $expectedData = $data;
        $expectedData['data']['id'] = $offerId;
        $expectedData['data']['attributes']['checksum'] = $expectedChecksum;
        $this->assertResponseContains($expectedData, $response);
        self::assertEquals($expectedChecksum, $offer->getChecksum());
    }

    public function testUpdate(): void
    {
        $offerId = $this->getReference('sale.quote.1.product-1.offer.1')->getId();
        $productUnitCode = $this->getReference('product_unit.bottle')->getCode();

        $response = $this->patch(
            ['entity' => 'quoteproductoffers', 'id' => (string)$offerId],
            [
                'data' => [
                    'type' => 'quoteproductoffers',
                    'id' => (string)$offerId,
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
                    'type' => 'quoteproductoffers',
                    'id' => (string)$offerId,
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

        /** @var QuoteProductOffer $updatedOffer */
        $updatedOffer = $this->getEntityManager()->find(QuoteProductOffer::class, $offerId);
        self::assertSame(10.0, $updatedOffer->getQuantity());
        self::assertEquals($productUnitCode, $updatedOffer->getProductUnitCode());
        self::assertEquals($productUnitCode, $updatedOffer->getProductUnit()->getCode());
    }

    public function testTryToUpdateQuoteProduct(): void
    {
        $offerId = $this->getReference('sale.quote.1.product-1.offer.1')->getId();
        $oldQuoteProductId = $this->getReference('sale.quote.1.product-1')->getId();

        $response = $this->patch(
            ['entity' => 'quoteproductoffers', 'id' => (string)$offerId],
            [
                'data' => [
                    'type' => 'quoteproductoffers',
                    'id' => (string)$offerId,
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
                    'type' => 'quoteproductoffers',
                    'id' => (string)$offerId,
                    'relationships' => [
                        'quoteProduct' => [
                            'data' => ['type' => 'quoteproducts', 'id' => (string)$oldQuoteProductId]
                        ]
                    ]
                ]
            ],
            $response
        );

        /** @var QuoteProductOffer $updatedOffer */
        $updatedOffer = $this->getEntityManager()->find(QuoteProductOffer::class, $offerId);
        self::assertEquals($oldQuoteProductId, $updatedOffer->getQuoteProduct()->getId());
    }

    public function testTryToUpdateReadonlyChecksum(): void
    {
        $offerId = $this->getReference('sale.quote.1.product-1.offer.1')->getId();

        $data = [
            'data' => [
                'type' => 'quoteproductoffers',
                'id' => (string)$offerId,
                'attributes' => [
                    'checksum' => '123456789'
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'quoteproductoffers', 'id' => (string)$offerId],
            $data
        );

        /** @var QuoteProductOffer $updatedOffer */
        $updatedOffer = $this->getEntityManager()->find(QuoteProductOffer::class, $offerId);
        $expectedChecksum = $this->generateQuoteProductOfferChecksum($updatedOffer);
        $expectedData = $data;
        $expectedData['data']['attributes']['checksum'] = $expectedChecksum;
        $this->assertResponseContains($expectedData, $response);
        self::assertEquals($expectedChecksum, $updatedOffer->getChecksum());
    }

    public function testDelete(): void
    {
        $offerId = $this->getReference('sale.quote.1.product-1.offer.1')->getId();

        $this->delete(
            ['entity' => 'quoteproductoffers', 'id' => (string)$offerId]
        );

        $offer = $this->getEntityManager()->find(QuoteProductOffer::class, $offerId);
        self::assertTrue(null === $offer);
    }

    public function testDeleteList(): void
    {
        $offerId = $this->getReference('sale.quote.1.product-1.offer.1')->getId();

        $this->cdelete(
            ['entity' => 'quoteproductoffers'],
            ['filter' => ['id' => (string)$offerId]]
        );

        $offer = $this->getEntityManager()->find(QuoteProductOffer::class, $offerId);
        self::assertTrue(null === $offer);
    }

    public function testGetSubresourceForQuoteProduct(): void
    {
        $offerId = $this->getReference('sale.quote.1.product-1.offer.1')->getId();

        $response = $this->getSubresource(
            ['entity' => 'quoteproductoffers', 'id' => (string)$offerId, 'association' => 'quoteProduct']
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
        $offerId = $this->getReference('sale.quote.1.product-1.offer.1')->getId();

        $response = $this->getRelationship(
            ['entity' => 'quoteproductoffers', 'id' => (string)$offerId, 'association' => 'quoteProduct']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'quoteproducts', 'id' => '<toString(@sale.quote.1.product-1->id)>']],
            $response
        );
    }

    public function testTryToUpdateQuoteProductViaRelationship(): void
    {
        $offerId = $this->getReference('sale.quote.1.product-1.offer.1')->getId();

        $response = $this->patchRelationship(
            ['entity' => 'quoteproductoffers', 'id' => (string)$offerId, 'association' => 'quoteProduct'],
            ['data' => ['type' => 'quoteproducts', 'id' => '<toString(@sale.quote.1.product-2->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForProductUnit(): void
    {
        $offerId = $this->getReference('sale.quote.1.product-1.offer.1')->getId();

        $response = $this->getSubresource(
            ['entity' => 'quoteproductoffers', 'id' => (string)$offerId, 'association' => 'productUnit']
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
        $offerId = $this->getReference('sale.quote.1.product-1.offer.1')->getId();

        $response = $this->getRelationship(
            ['entity' => 'quoteproductoffers', 'id' => (string)$offerId, 'association' => 'productUnit']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.liter->code)>']],
            $response
        );
    }

    public function testUpdateProductUnitViaRelationship(): void
    {
        $offerId = $this->getReference('sale.quote.1.product-1.offer.1')->getId();
        $productUnitCode = $this->getReference('product_unit.bottle')->getCode();

        $this->patchRelationship(
            ['entity' => 'quoteproductoffers', 'id' => (string)$offerId, 'association' => 'productUnit'],
            ['data' => ['type' => 'productunits', 'id' => $productUnitCode]]
        );

        /** @var QuoteProductOffer $updatedOffer */
        $updatedOffer = $this->getEntityManager()->find(QuoteProductOffer::class, $offerId);
        self::assertEquals($productUnitCode, $updatedOffer->getProductUnit()->getCode());
        self::assertEquals($productUnitCode, $updatedOffer->getProductUnitCode());
    }
}
