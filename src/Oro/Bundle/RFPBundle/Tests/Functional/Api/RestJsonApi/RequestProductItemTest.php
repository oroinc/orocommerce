<?php

namespace Oro\Bundle\RFPBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ProductBundle\LineItemChecksumGenerator\LineItemChecksumGeneratorInterface;
use Oro\Bundle\RFPBundle\Entity\RequestProductItem;
use Oro\Bundle\RFPBundle\Tests\Functional\DataFixtures\LoadRequestData;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RequestProductItemTest extends RestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadRequestData::class]);
    }

    private function generateRequestProductItemChecksum(RequestProductItem $requestProductItem): string
    {
        /** @var LineItemChecksumGeneratorInterface $lineItemChecksumGenerator */
        $lineItemChecksumGenerator = self::getContainer()->get('oro_product.line_item_checksum_generator');
        $checksum = $lineItemChecksumGenerator->getChecksum($requestProductItem);
        self::assertNotEmpty($checksum, 'Impossible to generate the request product item checksum.');

        return $checksum;
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
        $entityId = $this->getReference('rfp.request.1.product_item.1')->getId();

        $response = $this->get(
            ['entity' => 'rfqproductitems', 'id' => (string)$entityId]
        );

        $this->assertResponseContains('get_request_product_item.yml', $response);
    }

    public function testCreateWithRequiredDataOnly(): void
    {
        $data = $this->getRequestData('create_request_product_item_min.yml');
        $response = $this->post(
            ['entity' => 'rfqproductitems'],
            $data
        );

        $entityId = $this->getResourceId($response);
        $expectedData = $data;
        $expectedData['data']['id'] = $entityId;
        $this->assertResponseContains($expectedData, $response);
    }

    public function testCreate(): void
    {
        $data = $this->getRequestData('create_request_product_item.yml');
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
        $data = $this->getRequestData('create_request_product_item_min.yml');
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
        $data = $this->getRequestData('create_request_product_item_min.yml');
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
        $data = $this->getRequestData('create_request_product_item_min.yml');
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

    public function testCreateWithReadonlyChecksum(): void
    {
        $data = $this->getRequestData('create_request_product_item_min.yml');
        $data['data']['attributes']['checksum'] = '123456789';
        $response = $this->post(['entity' => 'rfqproductitems'], $data);

        $entityId = $this->getResourceId($response);
        /** @var RequestProductItem $entity */
        $entity = $this->getEntityManager()->find(RequestProductItem::class, $entityId);
        $expectedChecksum = $this->generateRequestProductItemChecksum($entity);
        $expectedData = $data;
        $expectedData['data']['id'] = $entityId;
        $expectedData['data']['attributes']['checksum'] = $expectedChecksum;
        $this->assertResponseContains($expectedData, $response);
        self::assertEquals($expectedChecksum, $entity->getChecksum());
    }

    public function testUpdate(): void
    {
        $entityId = $this->getReference('rfp.request.1.product_item.1')->getId();
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

    public function testTryToUpdateReadonlyChecksum(): void
    {
        $entityId = $this->getReference('rfp.request.1.product_item.1')->getId();
        $data = [
            'data' => [
                'type' => 'rfqproductitems',
                'id' => (string)$entityId,
                'attributes' => [
                    'checksum' => '123456789'
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'rfqproductitems', 'id' => (string)$entityId],
            $data
        );

        /** @var RequestProductItem $entity */
        $entity = $this->getEntityManager()->find(RequestProductItem::class, $entityId);
        $expectedChecksum = $this->generateRequestProductItemChecksum($entity);
        $expectedData = $data;
        $expectedData['data']['attributes']['checksum'] = $expectedChecksum;
        $this->assertResponseContains($expectedData, $response);
        self::assertEquals($expectedChecksum, $entity->getChecksum());
    }

    public function testDelete(): void
    {
        $entityId = $this->getReference('rfp.request.1.product_item.1')->getId();
        $this->delete(
            ['entity' => 'rfqproductitems', 'id' => $entityId]
        );

        $entity = $this->getEntityManager()->find(RequestProductItem::class, $entityId);
        self::assertTrue(null === $entity);
    }

    public function testDeleteList(): void
    {
        $entityId = $this->getReference('rfp.request.1.product_item.1')->getId();
        $this->cdelete(
            ['entity' => 'rfqproductitems', 'id' => $entityId],
            ['filter' => ['id' => (string)$entityId]]
        );

        $entity = $this->getEntityManager()->find(RequestProductItem::class, $entityId);
        self::assertTrue(null === $entity);
    }
}
