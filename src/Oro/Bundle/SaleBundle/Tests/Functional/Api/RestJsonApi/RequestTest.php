<?php

namespace Oro\Bundle\SaleBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\Tests\Functional\Api\DataFixtures\AssignRequestToQuotes;
use Oro\Bundle\SaleBundle\Tests\Functional\DataFixtures\LoadQuoteData;

class RequestTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([LoadQuoteData::class, LoadRequestData::class, AssignRequestToQuotes::class]);
    }

    public function testGetList(): void
    {
        $request1Id = $this->getReference('rfp.request.1')->getId();
        $request2Id = $this->getReference('rfp.request.2')->getId();
        $response = $this->cget(
            ['entity' => 'rfqs'],
            [
                'filter[id]' => implode(',', [$request1Id, $request2Id]),
                'fields[rfqs]' => 'quotes'
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'rfqs',
                        'id' => (string)$request1Id,
                        'relationships' => [
                            'quotes' => [
                                'data' => [
                                    ['type' => 'quotes', 'id' => '<toString(@sale.quote.1->id)>'],
                                    ['type' => 'quotes', 'id' => '<toString(@sale.quote.2->id)>']
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'rfqs',
                        'id' => (string)$request2Id,
                        'relationships' => [
                            'quotes' => [
                                'data' => []
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet(): void
    {
        $requestId = $this->getReference('rfp.request.1')->getId();
        $response = $this->get(
            ['entity' => 'rfqs', 'id' => (string)$requestId]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'rfqs',
                    'id' => (string)$requestId,
                    'relationships' => [
                        'quotes' => [
                            'data' => [
                                ['type' => 'quotes', 'id' => '<toString(@sale.quote.1->id)>'],
                                ['type' => 'quotes', 'id' => '<toString(@sale.quote.2->id)>']
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToCreateWithQuotes(): void
    {
        $quoteId = $this->getReference('sale.quote.3')->getId();
        $data = $this->getRequestData('@OroRFPBundle/Tests/Functional/Api/RestJsonApi/requests/create_request.yml');
        $data['data']['relationships']['quotes']['data'][] = ['type' => 'quotes', 'id' => (string)$quoteId];
        $response = $this->post(['entity' => 'rfqs'], $data);
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'rfqs',
                    'relationships' => [
                        'quotes' => [
                            'data' => []
                        ]
                    ]
                ]
            ],
            $response
        );
        /** @var Quote $quote */
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertTrue(null === $quote->getRequest());
    }

    public function testTryToUpdateWithQuotes(): void
    {
        $requestId = $this->getReference('rfp.request.3')->getId();
        $quoteId = $this->getReference('sale.quote.3')->getId();
        $response = $this->patch(
            ['entity' => 'rfqs', 'id' => (string)$requestId],
            [
                'data' => [
                    'type' => 'rfqs',
                    'id' => (string)$requestId,
                    'relationships' => [
                        'quotes' => [
                            'data' => [
                                ['type' => 'quotes', 'id' => (string)$quoteId]
                            ]
                        ]
                    ]
                ]
            ]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'rfqs',
                    'id' => (string)$requestId,
                    'relationships' => [
                        'quotes' => [
                            'data' => []
                        ]
                    ]
                ]
            ],
            $response
        );
        /** @var Quote $quote */
        $quote = $this->getEntityManager()->find(Quote::class, $quoteId);
        self::assertTrue(null === $quote->getRequest());
    }

    public function testGetSubresourceForQuotes(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'rfqs', 'id' => '<toString(@rfp.request.1->id)>', 'association' => 'quotes']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'quotes',
                        'id' => '<toString(@sale.quote.1->id)>',
                        'attributes' => ['poNumber' => 'PO_SALE.QUOTE.1']
                    ],
                    [
                        'type' => 'quotes',
                        'id' => '<toString(@sale.quote.2->id)>',
                        'attributes' => ['poNumber' => 'PO_SALE.QUOTE.2']
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForQuotes(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'rfqs', 'id' => '<toString(@rfp.request.1->id)>', 'association' => 'quotes']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'quotes', 'id' => '<toString(@sale.quote.1->id)>'],
                    ['type' => 'quotes', 'id' => '<toString(@sale.quote.2->id)>']
                ]
            ],
            $response
        );
    }

    public function testTryToUpdateQuotesViaRelationship(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'rfqs', 'id' => '<toString(@rfp.request.1->id)>', 'association' => 'quotes'],
            ['data' => [['type' => 'quotes', 'id' => '<toString(@sale.quote.1->id)>']]],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToAddQuotesViaRelationship(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'rfqs', 'id' => '<toString(@rfp.request.1->id)>', 'association' => 'quotes'],
            ['data' => [['type' => 'quotes', 'id' => '<toString(@sale.quote.3->id)>']]],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToRemoveQuotesViaRelationship(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'rfqs', 'id' => '<toString(@rfp.request.1->id)>', 'association' => 'quotes'],
            ['data' => [['type' => 'quotes', 'id' => '<toString(@sale.quote.1->id)>']]],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
