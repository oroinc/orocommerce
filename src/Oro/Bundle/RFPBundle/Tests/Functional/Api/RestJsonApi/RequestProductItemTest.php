<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

/**
 * @dbIsolationPerTest
 */
class RequestProductItemTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadRequestData::class]);
    }

    private function getRequestProductItemId(): int
    {
        /** @var Request $request */
        $request = $this->getReference('rfp.request.1');
        /** @var RequestProduct $requestProduct */
        $requestProduct = $request->getRequestProducts()->first();
        /** @var RequestProductItem $requestProductItem */
        $requestProductItem = $requestProduct->getRequestProductItems()->first();

        return $requestProductItem->getId();
    }

    private function getCreateData(): array
    {
        /** @var Request $request */
        $request = $this->getReference(LoadRequestData::REQUEST1);
        $requestProduct = $request->getRequestProducts()->first();

        return [
            'data' => [
                'type' => 'rfqproductitems',
                'attributes' => [
                    'quantity' => 10,
                    'value' => '100.0000',
                    'currency' => 'USD'
                ],
                'relationships' => [
                    'productUnit' => [
                        'data' => ['type' => 'productunits', 'id' => '<toString(@product_unit.liter->code)>']
                    ],
                    'requestProduct' => [
                        'data' => ['type' => 'rfqproducts', 'id' => (string)$requestProduct->getId()]
                    ]
                ]
            ]
        ];
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'rfqproductitems'],
            ['page' => ['size' => 1000]]
        );

        $expectedCount = LoadRequestData::NUM_REQUESTS
            * LoadRequestData::NUM_LINE_ITEMS
            * LoadRequestData::NUM_PRODUCTS;

        self::assertResponseCount($expectedCount, $response);
    }

    public function testGet(): void
    {
        $entity = $this->getEntityManager()
            ->getRepository(RequestProductItem::class)
            ->findOneBy([]);

        $response = $this->get(
            ['entity' => 'rfqproductitems', 'id' => $entity->getId()]
        );

        self::assertResponseNotEmpty($response);
    }

    public function testCreate(): void
    {
        $data = $this->getCreateData();
        $response = $this->post(
            ['entity' => 'rfqproductitems'],
            $data
        );

        $entityId = $this->getResourceId($response);
        $expectedData = $data;
        $expectedData['data']['id'] = $entityId;
        $this->assertResponseContains($expectedData, $response);
    }

    public function testTryToCreateWithEmptyValue(): void
    {
        $data = $this->getCreateData();
        $data['data']['attributes']['value'] = '';
        $response = $this->post(
            ['entity' => 'rfqproductitems'],
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
        $data = $this->getCreateData();
        $data['data']['attributes']['currency'] = '';
        $response = $this->post(
            ['entity' => 'rfqproductitems'],
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
        $data = $this->getCreateData();
        $data['data']['attributes']['value'] = 'test';
        $response = $this->post(
            ['entity' => 'rfqproductitems'],
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

    public function testUpdate(): void
    {
        $entityId = $this->getRequestProductItemId();
        $data = [
            'data' => [
                'type' => 'rfqproductitems',
                'id' => (string)$entityId,
                'attributes' => [
                    'value' => 150
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'rfqproductitems', 'id' => $entityId],
            $data
        );

        $result = self::jsonToArray($response->getContent());
        self::assertEquals(150, $result['data']['attributes']['value']);
    }

    public function testDelete(): void
    {
        $entityId = $this->getRequestProductItemId();
        $this->delete(
            ['entity' => 'rfqproductitems', 'id' => $entityId]
        );

        $entity = $this->getEntityManager()->find(RequestProductItem::class, $entityId);
        self::assertTrue(null === $entity);
    }
}
