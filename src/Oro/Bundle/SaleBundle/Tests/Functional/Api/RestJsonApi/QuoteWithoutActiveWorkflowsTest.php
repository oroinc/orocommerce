<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ActivityBundle\EntityConfig\ActivityScope;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\NoteBundle\Entity\Note;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Entity\QuoteAddress;
use Oro\Bundle\SaleBundle\Entity\QuoteProduct;
use Oro\Bundle\SaleBundle\Entity\QuoteProductOffer;
use Oro\Bundle\SaleBundle\Entity\QuoteProductRequest;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class QuoteWithoutActiveWorkflowsTest extends RestJsonApiTestCase
{
    private array $deactivatedWorkflows;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadQuoteData::class,
            LoadRequestData::class,
            '@OroSaleBundle/Tests/Functional/Api/DataFixtures/quote_notes.yml'
        ]);

        $this->deactivatedWorkflows = $this->deactivateActiveWorkflows();

        // guard
        self::assertFalse($this->getWorkflowRegistry()->hasActiveWorkflowsByEntityClass(Quote::class));
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->activateWorkflows($this->deactivatedWorkflows);
        parent::tearDown();
    }

    private function getWorkflowRegistry(): WorkflowRegistry
    {
        return self::getContainer()->get('oro_workflow.registry');
    }

    private function getWorkflowManager(): WorkflowManager
    {
        return self::getContainer()->get('oro_workflow.manager');
    }

    private function deactivateActiveWorkflows(): array
    {
        $deactivatedWorkflows = [];
        $workflowManager = $this->getWorkflowManager();
        $activeWorkflows = $this->getWorkflowRegistry()->getActiveWorkflowsByEntityClass(Quote::class);
        foreach ($activeWorkflows as $workflow) {
            $deactivatedWorkflows[] = $workflow->getName();
            $workflowManager->deactivateWorkflow($workflow->getName());
        }

        return $deactivatedWorkflows;
    }

    private function activateWorkflows(array $workflowNames): void
    {
        $workflowManager = $this->getWorkflowManager();
        foreach ($workflowNames as $workflowName) {
            $workflowManager->activateWorkflow($workflowName);
        }
    }

    private function getActivityNoteIds(int $quoteId): array
    {
        $rows = $this->getEntityManager()->createQueryBuilder()
            ->from(Note::class, 't')
            ->select('t.id')
            ->join('t.' . ExtendHelper::buildAssociationName(Quote::class, ActivityScope::ASSOCIATION_KIND), 'c')
            ->where('c.id = :targetEntityId')
            ->setParameter('targetEntityId', $quoteId)
            ->orderBy('t.id')
            ->getQuery()
            ->getArrayResult();

        return array_column($rows, 'id');
    }

    public function testCreateWithInternalStatus(): void
    {
        $data = [
            'data' => [
                'type' => 'quotes',
                'relationships' => [
                    'internalStatus' => [
                        'data' => ['type' => 'quoteinternalstatuses', 'id' => 'sent_to_customer']
                    ]
                ]
            ]
        ];
        $response = $this->post(['entity' => 'quotes'], $data);

        $quoteId = (int)$this->getResourceId($response);
        /** @var Quote $quote */
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertTrue(null !== $quote);

        $this->assertResponseContains($data, $response);

        self::assertEquals('sent_to_customer', $quote->getInternalStatus()->getInternalId());
    }

    public function testTryToUpdateInternalStatus(): void
    {
        $quoteId = $this->getReference('sale.quote.1')->getId();

        $data = [
            'data' => [
                'type' => 'quotes',
                'id' => (string)$quoteId,
                'relationships' => [
                    'internalStatus' => [
                        'data' => ['type' => 'quoteinternalstatuses', 'id' => 'sent_to_customer']
                    ]
                ]
            ]
        ];
        $response = $this->patch(['entity' => 'quotes', 'id' => (string)$quoteId], $data);

        $this->assertResponseContains($data, $response);

        /** @var Quote $quote */
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertEquals('sent_to_customer', $quote->getInternalStatus()->getInternalId());
    }

    public function testUpdateMarkedAsDeleted(): void
    {
        $quoteId = $this->getReference('sale.quote.2')->getId();

        $data = [
            'data' => [
                'type' => 'quotes',
                'id' => (string)$quoteId,
                'attributes' => [
                    'poNumber' => 'UPDATED'
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
        self::assertEquals('UPDATED', $quote->getPoNumber());
    }

    public function testUpdateInternalStatusViaRelationship(): void
    {
        $quoteId = $this->getReference('sale.quote.1')->getId();

        $this->patchRelationship(
            ['entity' => 'quotes', 'id' => (string)$quoteId, 'association' => 'internalStatus'],
            [
                'data' => [
                    'type' => 'quoteinternalstatuses',
                    'id' => 'sent_to_customer'
                ]
            ]
        );

        /** @var Quote $quote */
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertEquals('sent_to_customer', $quote->getInternalStatus()->getInternalId());
    }

    public function testUpdateAssignedCustomerUsersViaRelationshipMarkedAsDeleted(): void
    {
        $quoteId = $this->getReference('sale.quote.2')->getId();

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

    public function testUpdateActivityNotesViaRelationship(): void
    {
        $quoteId = $this->getReference('sale.quote.1')->getId();
        $note1Id = $this->getReference('note1')->getId();
        $note3Id = $this->getReference('note3')->getId();

        $this->patchRelationship(
            ['entity' => 'quotes', 'id' => (string)$quoteId, 'association' => 'activityNotes'],
            [
                'data' => [
                    ['type' => 'notes', 'id' => (string)$note1Id],
                    ['type' => 'notes', 'id' => (string)$note3Id]
                ]
            ]
        );

        self::assertEquals([$note1Id, $note3Id], $this->getActivityNoteIds($quoteId));
    }

    public function testRemoveActivityNotesViaRelationship(): void
    {
        $quoteId = $this->getReference('sale.quote.1')->getId();
        $note1Id = $this->getReference('note1')->getId();
        $note2Id = $this->getReference('note2')->getId();

        $this->deleteRelationship(
            ['entity' => 'quotes', 'id' => (string)$quoteId, 'association' => 'activityNotes'],
            [
                'data' => [
                    ['type' => 'notes', 'id' => (string)$note1Id]
                ]
            ]
        );

        self::assertEquals([$note2Id], $this->getActivityNoteIds($quoteId));
    }

    public function testAddActivityNotesViaRelationship(): void
    {
        $quoteId = $this->getReference('sale.quote.1')->getId();
        $note1Id = $this->getReference('note1')->getId();
        $note2Id = $this->getReference('note2')->getId();
        $note3Id = $this->getReference('note3')->getId();

        $this->postRelationship(
            ['entity' => 'quotes', 'id' => (string)$quoteId, 'association' => 'activityNotes'],
            [
                'data' => [
                    ['type' => 'notes', 'id' => (string)$note3Id]
                ]
            ]
        );

        self::assertEquals([$note1Id, $note2Id, $note3Id], $this->getActivityNoteIds($quoteId));
    }

    public function testUpdateActivityNotesViaRelationshipMarkedAsDeleted(): void
    {
        $quoteId = $this->getReference('sale.quote.2')->getId();
        $note2Id = $this->getReference('note2')->getId();
        $note3Id = $this->getReference('note3')->getId();

        $this->patchRelationship(
            ['entity' => 'quotes', 'id' => (string)$quoteId, 'association' => 'activityNotes'],
            [
                'data' => [
                    ['type' => 'notes', 'id' => (string)$note2Id],
                    ['type' => 'notes', 'id' => (string)$note3Id]
                ]
            ]
        );

        self::assertEquals([$note2Id, $note3Id], $this->getActivityNoteIds($quoteId));
    }

    public function testCreateShippingAddressForQuoteMarkedAsDeleted(): void
    {
        $data = [
            'data' => [
                'type' => 'quoteshippingaddresses',
                'relationships' => [
                    'country' => [
                        'data' => ['type' => 'countries', 'id' => '<toString(@country.US->iso2Code)>']
                    ],
                    'quote' => [
                        'data' => ['type' => 'quotes', 'id' => '<toString(@sale.quote.2->id)>']
                    ]
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => 'quoteshippingaddresses'],
            $data
        );

        $this->assertResponseContains($data, $response);

        $addressId = (int)$this->getResourceId($response);
        $address = $this->getEntityManager()->find(QuoteAddress::class, $addressId);
        self::assertTrue(null !== $address);
    }

    public function testUpdateShippingAddressForQuoteMarkedAsDeleted(): void
    {
        /** @var Quote $quote */
        $quote = $this->getReference('sale.quote.2');
        $address = new QuoteAddress();
        $address->setCountry($this->getReference('country.US'));
        $quote->setShippingAddress($address);
        $this->getEntityManager()->flush();
        $addressId = $address->getId();

        $data = [
            'data' => [
                'type' => 'quoteshippingaddresses',
                'id' => (string)$addressId,
                'attributes' => [
                    'city' => 'UPDATED'
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'quoteshippingaddresses', 'id' => (string)$addressId],
            $data
        );

        $this->assertResponseContains($data, $response);

        $address = $this->getEntityManager()->find(QuoteAddress::class, $addressId);
        self::assertEquals('UPDATED', $address->getCity());
    }

    public function testCreateProductForQuoteMarkedAsDeleted(): void
    {
        $data = $this->getRequestData('create_quote_product_min.yml');
        $data['data']['relationships']['quote']['data']['id'] = '<toString(@sale.quote.2->id)>';
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
        $expectedData['data']['attributes']['productSku'] = 'product-2';
        $expectedData['data']['relationships']['quoteProductOffers']['data'][0]['id'] = (string)$offer->getId();
        $expectedData['included'][0]['id'] = (string)$offer->getId();
        $this->assertResponseContains($expectedData, $response);
    }

    public function testUpdateProductForQuoteMarkedAsDeleted(): void
    {
        /** @var Quote $quote */
        $quote = $this->getReference('sale.quote.2');
        $quoteProduct = new QuoteProduct();
        $quoteProduct->setProduct($this->getReference('product-2'));
        $quote->addQuoteProduct($quoteProduct);
        $this->getEntityManager()->flush();
        $quoteProductId = $quoteProduct->getId();

        $data = [
            'data' => [
                'type' => 'quoteproducts',
                'id' => (string)$quoteProductId,
                'relationships' => [
                    'product' => [
                        'data' => ['type' => 'products', 'id' => '<toString(@product-1->id)>']
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId],
            $data
        );

        $this->assertResponseContains($data, $response);

        $quoteProduct = $this->getEntityManager()->find(QuoteProduct::class, $quoteProductId);
        self::assertEquals($this->getReference('product-1')->getId(), $quoteProduct->getProduct()->getId());
    }

    public function testCreateProductRequestForQuoteMarkedAsDeleted(): void
    {
        /** @var Quote $quote */
        $quote = $this->getReference('sale.quote.2');
        $quoteProduct = new QuoteProduct();
        $quoteProduct->setProduct($this->getReference('product-2'));
        $quote->addQuoteProduct($quoteProduct);
        $this->getEntityManager()->flush();
        $quoteProductId = $quoteProduct->getId();

        $data = $this->getRequestData('create_quote_product_request_min.yml');
        $data['data']['relationships']['quoteProduct']['data']['id'] = (string)$quoteProductId;
        $response = $this->post(
            ['entity' => 'quoteproductrequests'],
            $data
        );

        $this->assertResponseContains($data, $response);

        $quoteProductRequestId = (int)$this->getResourceId($response);
        $quoteProductRequest = $this->getEntityManager()->find(QuoteProductRequest::class, $quoteProductRequestId);
        self::assertTrue(null !== $quoteProductRequest);
    }

    public function testUpdateProductRequestForQuoteMarkedAsDeleted(): void
    {
        /** @var Quote $quote */
        $quote = $this->getReference('sale.quote.2');
        $quoteProduct = new QuoteProduct();
        $quoteProduct->setProduct($this->getReference('product-2'));
        $quote->addQuoteProduct($quoteProduct);
        $quoteProductRequest = new QuoteProductRequest();
        $quoteProductRequest->setProductUnit($this->getReference('product_unit.bottle'));
        $quoteProduct->addQuoteProductRequest($quoteProductRequest);
        $this->getEntityManager()->flush();
        $quoteProductRequestId = $quoteProductRequest->getId();

        $data = [
            'data' => [
                'type' => 'quoteproductrequests',
                'id' => (string)$quoteProductRequestId,
                'relationships' => [
                    'productUnit' => [
                        'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.box->code)>']
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'quoteproductrequests', 'id' => (string)$quoteProductRequestId],
            $data
        );

        $this->assertResponseContains($data, $response);

        $quoteProductRequest = $this->getEntityManager()->find(QuoteProductRequest::class, $quoteProductRequestId);
        self::assertEquals(
            $this->getReference('product_unit.box')->getCode(),
            $quoteProductRequest->getProductUnit()->getCode()
        );
    }

    public function testCreateProductOfferForQuoteMarkedAsDeleted(): void
    {
        /** @var Quote $quote */
        $quote = $this->getReference('sale.quote.2');
        $quoteProduct = new QuoteProduct();
        $quoteProduct->setProduct($this->getReference('product-2'));
        $quote->addQuoteProduct($quoteProduct);
        $this->getEntityManager()->flush();
        $quoteProductId = $quoteProduct->getId();

        $data = $this->getRequestData('create_quote_product_offer_min.yml');
        $data['data']['relationships']['quoteProduct']['data']['id'] = (string)$quoteProductId;
        $response = $this->post(
            ['entity' => 'quoteproductoffers'],
            $data
        );

        $this->assertResponseContains($data, $response);

        $quoteProductOfferId = (int)$this->getResourceId($response);
        $quoteProductOffer = $this->getEntityManager()->find(QuoteProductOffer::class, $quoteProductOfferId);
        self::assertTrue(null !== $quoteProductOffer);
    }

    public function testUpdateProductOfferForQuoteMarkedAsDeleted(): void
    {
        /** @var Quote $quote */
        $quote = $this->getReference('sale.quote.2');
        $quoteProduct = new QuoteProduct();
        $quoteProduct->setProduct($this->getReference('product-2'));
        $quote->addQuoteProduct($quoteProduct);
        $quoteProductOffer = new QuoteProductOffer();
        $quoteProductOffer->setQuantity(1.1);
        $quoteProductOffer->setPrice(Price::create(1, 'USD'));
        $quoteProductOffer->setProductUnit($this->getReference('product_unit.bottle'));
        $quoteProduct->addQuoteProductOffer($quoteProductOffer);
        $this->getEntityManager()->flush();
        $quoteProductOfferId = $quoteProductOffer->getId();

        $data = [
            'data' => [
                'type' => 'quoteproductoffers',
                'id' => (string)$quoteProductOfferId,
                'relationships' => [
                    'productUnit' => [
                        'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.box->code)>']
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'quoteproductoffers', 'id' => (string)$quoteProductOfferId],
            $data
        );

        $this->assertResponseContains($data, $response);

        $quoteProductOffer = $this->getEntityManager()->find(QuoteProductOffer::class, $quoteProductOfferId);
        self::assertEquals(
            $this->getReference('product_unit.box')->getCode(),
            $quoteProductOffer->getProductUnit()->getCode()
        );
    }

    public function testUpdateProductForQuoteProductViaRelationshipForQuoteMarkedAsDeleted(): void
    {
        /** @var Quote $quote */
        $quote = $this->getReference('sale.quote.2');
        $quoteProduct = new QuoteProduct();
        $quoteProduct->setProduct($this->getReference('product-1'));
        $quote->addQuoteProduct($quoteProduct);
        $quoteProductOffer = new QuoteProductOffer();
        $quoteProductOffer->setQuantity(1.1);
        $quoteProductOffer->setPrice(Price::create(1, 'USD'));
        $quoteProductOffer->setProductUnit($this->getReference('product_unit.bottle'));
        $quoteProduct->addQuoteProductOffer($quoteProductOffer);
        $this->getEntityManager()->flush();
        $quoteProductId = $quoteProduct->getId();
        $productId = $this->getReference('product-2')->getId();

        $this->patchRelationship(
            ['entity' => 'quoteproducts', 'id' => (string)$quoteProductId, 'association' => 'product'],
            ['data' => ['type' => 'products', 'id' => (string)$productId]]
        );

        /** @var QuoteProduct $updatedQuoteProduct */
        $updatedQuoteProduct = $this->getEntityManager()->find(QuoteProduct::class, $quoteProductId);
        self::assertEquals($productId, $updatedQuoteProduct->getProduct()->getId());
    }

    public function testUpdateProductUnitForQuoteProductRequestViaRelationshipForQuoteMarkedAsDeleted(): void
    {
        /** @var Quote $quote */
        $quote = $this->getReference('sale.quote.2');
        $quoteProduct = new QuoteProduct();
        $quoteProduct->setProduct($this->getReference('product-2'));
        $quote->addQuoteProduct($quoteProduct);
        $quoteProductRequest = new QuoteProductRequest();
        $quoteProductRequest->setQuantity(1.1);
        $quoteProductRequest->setProductUnit($this->getReference('product_unit.bottle'));
        $quoteProduct->addQuoteProductRequest($quoteProductRequest);
        $this->getEntityManager()->flush();
        $quoteProductRequestId = $quoteProductRequest->getId();
        $productUnitCode = $this->getReference('product_unit.box')->getCode();

        $this->patchRelationship(
            [
                'entity' => 'quoteproductrequests',
                'id' => (string)$quoteProductRequestId,
                'association' => 'productUnit'
            ],
            ['data' => ['type' => 'productunits', 'id' => $productUnitCode]]
        );

        /** @var QuoteProductRequest $updatedQuoteProductRequest */
        $updatedQuoteProductRequest = $this->getEntityManager()->find(
            QuoteProductRequest::class,
            $quoteProductRequestId
        );
        self::assertEquals($productUnitCode, $updatedQuoteProductRequest->getProductUnit()->getCode());
    }

    public function testUpdateProductUnitForQuoteProductOfferViaRelationshipForQuoteMarkedAsDeleted(): void
    {
        /** @var Quote $quote */
        $quote = $this->getReference('sale.quote.2');
        $quoteProduct = new QuoteProduct();
        $quoteProduct->setProduct($this->getReference('product-2'));
        $quote->addQuoteProduct($quoteProduct);
        $quoteProductOffer = new QuoteProductOffer();
        $quoteProductOffer->setQuantity(1.1);
        $quoteProductOffer->setPrice(Price::create(1, 'USD'));
        $quoteProductOffer->setProductUnit($this->getReference('product_unit.bottle'));
        $quoteProduct->addQuoteProductOffer($quoteProductOffer);
        $this->getEntityManager()->flush();
        $quoteProductOfferId = $quoteProductOffer->getId();
        $productUnitCode = $this->getReference('product_unit.box')->getCode();

        $this->patchRelationship(
            [
                'entity' => 'quoteproductoffers',
                'id' => (string)$quoteProductOfferId,
                'association' => 'productUnit'
            ],
            ['data' => ['type' => 'productunits', 'id' => $productUnitCode]]
        );

        /** @var QuoteProductOffer $updatedQuoteProductOffer */
        $updatedQuoteProductOffer = $this->getEntityManager()->find(
            QuoteProductOffer::class,
            $quoteProductOfferId
        );
        self::assertEquals($productUnitCode, $updatedQuoteProductOffer->getProductUnit()->getCode());
    }
}
