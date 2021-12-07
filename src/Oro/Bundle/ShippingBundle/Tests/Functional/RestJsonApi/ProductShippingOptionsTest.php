<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits;
use Oro\Bundle\ShippingBundle\Entity\ProductShippingOptions;
use Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures\LoadProductShippingOptions;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductShippingOptionsTest extends RestJsonApiTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadProductShippingOptions::class]);
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'productshippingoptions'],
            []
        );

        $this->assertResponseContains('product_shipping_options/get_list.yml', $response);
    }

    public function testCreate()
    {
        $this->post(
            ['entity' => 'productshippingoptions'],
            'product_shipping_options/create.yml'
        );

        /** @var ProductShippingOptions $shippingOption */
        $shippingOption = $this->getEntityManager()
            ->getRepository(ProductShippingOptions::class)
            ->findOneBy([
                'product'        => $this->getReference(LoadProductData::PRODUCT_3),
                'productUnit'    => $this->getReference(LoadProductUnits::LITER),
                'weightUnit'     => $this->getReference('weight_unit.kilo'),
                'dimensionsUnit' => $this->getReference('length_unit.in'),
                'freightClass'   => $this->getReference('freight_class.pcl')
            ]);

        self::assertEquals(24.57, $shippingOption->getWeight()->getValue());
        self::assertSame('kilo', $shippingOption->getWeight()->getUnit()->getCode());
        self::assertEquals(31.51, $shippingOption->getDimensions()->getValue()->getLength());
        self::assertEquals(33.16, $shippingOption->getDimensions()->getValue()->getWidth());
        self::assertEquals(128.09, $shippingOption->getDimensions()->getValue()->getHeight());
        self::assertSame('in', $shippingOption->getDimensions()->getUnit()->getCode());
        self::assertSame('pcl', $shippingOption->getFreightClass()->getCode());
    }

    public function testTryToCreateDuplicate()
    {
        $routeParameters = ['entity' => 'productshippingoptions'];
        $parameters = $this->getRequestData('product_shipping_options/create.yml');

        $this->post($routeParameters, $parameters);

        $response = $this->post($routeParameters, $parameters, [], false);

        $this->assertResponseValidationError(
            [
                'title'  => 'unique entity constraint',
                'detail' => 'Product has duplication of shipping options. Field "Unit" should be unique.'
            ],
            $response
        );
    }

    public function testTryToCreateEmptyWeightValue()
    {
        $data = $this->getRequestData('product_shipping_options/create.yml');
        $data['data']['attributes']['weightValue'] = '';
        $response = $this->post(
            ['entity' => 'productshippingoptions'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title'  => 'form constraint',
                'detail' => 'This value is not valid.',
                'source' => ['pointer' => '/data/attributes/weightValue']
            ],
            $response
        );
    }

    public function testTryToCreateEmptyDimensionsValues()
    {
        $data = $this->getRequestData('product_shipping_options/create.yml');
        $data['data']['attributes']['dimensionsLength'] = '';
        $response = $this->post(
            ['entity' => 'productshippingoptions'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title'  => 'form constraint',
                'detail' => 'This value is not valid.',
                'source' => ['pointer' => '/data/attributes/dimensionsLength']
            ],
            $response
        );

        $data = $this->getRequestData('product_shipping_options/create.yml');
        $data['data']['attributes']['dimensionsLength'] = 31.51;
        $data['data']['attributes']['dimensionsWidth'] = '';
        $response = $this->post(
            ['entity' => 'productshippingoptions'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title'  => 'form constraint',
                'detail' => 'This value is not valid.',
                'source' => ['pointer' => '/data/attributes/dimensionsWidth']
            ],
            $response
        );

        $data = $this->getRequestData('product_shipping_options/create.yml');
        $data['data']['attributes']['dimensionsWidth'] = 33.16;
        $data['data']['attributes']['dimensionsHeight'] = '';
        $response = $this->post(
            ['entity' => 'productshippingoptions'],
            $data,
            [],
            false
        );

        $this->assertResponseContainsValidationError(
            [
                'title'  => 'form constraint',
                'detail' => 'This value is not valid.',
                'source' => ['pointer' => '/data/attributes/dimensionsHeight']
            ],
            $response
        );
    }

    public function testTryToCreateWrongWeightUnit()
    {
        $data = $this->getRequestData('product_shipping_options/create.yml');
        $data['data']['relationships']['weightUnit']['data']['id'] = 'kilogram';
        $response = $this->post(
            ['entity' => 'productshippingoptions'],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'form constraint',
                'detail' => 'The entity does not exist.',
                'source' => ['pointer' => '/data/relationships/weightUnit/data']
            ],
            $response
        );
    }

    public function testDeleteList()
    {
        $optionId1 = $this->getReference(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_1)->getId();
        $optionId2 = $this->getReference(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_2)->getId();

        $this->cdelete(
            ['entity' => 'productshippingoptions'],
            ['filter' => ['id' => [$optionId1, $optionId2]]]
        );

        $this->assertNull(
            $this->getEntityManager()->find(ProductShippingOptions::class, $optionId1)
        );

        $this->assertNull(
            $this->getEntityManager()->find(ProductShippingOptions::class, $optionId2)
        );
    }

    public function testGet()
    {
        $optionId = $this->getReference(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_1)->getId();

        $response = $this->get(
            ['entity' => 'productshippingoptions', 'id' => $optionId]
        );

        $this->assertResponseContains('product_shipping_options/get.yml', $response);
    }

    public function testUpdate()
    {
        $optionId = $this->getReference(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_1)->getId();

        $this->patch(
            ['entity' => 'productshippingoptions', 'id' => (string)$optionId],
            'product_shipping_options/update.yml'
        );

        $shippingOption = $this->getEntityManager()
            ->getRepository(ProductShippingOptions::class)
            ->find($optionId);

        self::assertEquals(6.6, $shippingOption->getWeight()->getValue());
        self::assertEquals('pound', $shippingOption->getWeight()->getUnit()->getCode());
        self::assertEquals(77.77, $shippingOption->getDimensions()->getValue()->getLength());
        self::assertEquals(88.88, $shippingOption->getDimensions()->getValue()->getWidth());
        self::assertEquals(99.99, $shippingOption->getDimensions()->getValue()->getHeight());
        self::assertEquals('meter', $shippingOption->getDimensions()->getUnit()->getCode());
    }

    public function testTryToUpdateToDuplicate()
    {
        $optionId = $this->getReference(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_1)->getId();

        $response = $this->patch(
            ['entity' => 'productshippingoptions', 'id' => (string)$optionId],
            'product_shipping_options/update_duplicate.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'unique entity constraint',
                'detail' => 'Product has duplication of shipping options. Field "Unit" should be unique.'
            ],
            $response
        );
    }

    public function testDelete()
    {
        $optionId = $this->getReference(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_1)->getId();

        $this->delete(
            ['entity' => 'productshippingoptions', 'id' => $optionId]
        );

        $this->assertNull(
            $this->getEntityManager()->find(ProductShippingOptions::class, $optionId)
        );
    }

    public function testGetSubResources()
    {
        /** @var ProductShippingOptions $option */
        $option = $this->getReference(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_1);

        $this->assertGetSubResource($option->getId(), 'product', $option->getProduct()->getId());
        $this->assertGetSubResource($option->getId(), 'productUnit', $option->getProductUnit()->getCode());
        $this->assertGetSubResource($option->getId(), 'weightUnit', $option->getWeight()->getUnit()->getCode());
        $this->assertGetSubResource($option->getId(), 'dimensionsUnit', $option->getDimensions()->getUnit()->getCode());
        $this->assertGetSubResource($option->getId(), 'freightClass', $option->getFreightClass()->getCode());
    }

    public function testGetRelationships()
    {
        /** @var ProductShippingOptions $option */
        $option = $this->getReference(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_1);

        $response = $this->getRelationship([
            'entity'      => 'productshippingoptions',
            'id'          => $option->getId(),
            'association' => 'product'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'products',
                    'id'   => (string)$option->getProduct()->getId()
                ]
            ],
            $response
        );

        $response = $this->getRelationship([
            'entity'      => 'productshippingoptions',
            'id'          => $option->getId(),
            'association' => 'productUnit'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'productunits',
                    'id'   => (string)$option->getProductUnitCode()
                ]
            ],
            $response
        );

        $response = $this->getRelationship([
            'entity'      => 'productshippingoptions',
            'id'          => $option->getId(),
            'association' => 'weightUnit'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'weightunits',
                    'id'   => (string)$option->getWeight()->getUnit()->getCode()
                ]
            ],
            $response
        );

        $response = $this->getRelationship([
            'entity'      => 'productshippingoptions',
            'id'          => $option->getId(),
            'association' => 'dimensionsUnit'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'lengthunits',
                    'id'   => (string)$option->getDimensions()->getUnit()->getCode()
                ]
            ],
            $response
        );

        $response = $this->getRelationship([
            'entity'      => 'productshippingoptions',
            'id'          => $option->getId(),
            'association' => 'freightClass'
        ]);

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'freightclasses',
                    'id'   => (string)$option->getFreightClass()->getCode()
                ]
            ],
            $response
        );
    }

    public function testPatchRelationships()
    {
        /** @var ProductShippingOptions $option */
        $option = $this->getReference(LoadProductShippingOptions::PRODUCT_SHIPPING_OPTIONS_1);

        $this->patchRelationship(
            [
                'entity'      => 'productshippingoptions',
                'id'          => $option->getId(),
                'association' => 'productUnit'
            ],
            [
                'data' => [
                    'type' => 'productunits',
                    'id'   => (string)$this->getReference('product_unit.milliliter')->getCode()
                ]
            ]
        );

        $this->patchRelationship(
            [
                'entity'      => 'productshippingoptions',
                'id'          => $option->getId(),
                'association' => 'product'
            ],
            [
                'data' => [
                    'type' => 'products',
                    'id'   => (string)$this->getReference('product-4')->getId()
                ]
            ]
        );

        $this->patchRelationship(
            [
                'entity'      => 'productshippingoptions',
                'id'          => $option->getId(),
                'association' => 'weightUnit'
            ],
            [
                'data' => [
                    'type' => 'weightunits',
                    'id'   => (string)$this->getReference('weight_unit.pound')
                ]
            ]
        );

        $this->patchRelationship(
            [
                'entity'      => 'productshippingoptions',
                'id'          => $option->getId(),
                'association' => 'dimensionsUnit'
            ],
            [
                'data' => [
                    'type' => 'lengthunits',
                    'id'   => (string)$this->getReference('length_unit.meter')
                ]
            ]
        );

        $updatedOption = $this->getEntityManager()
            ->getRepository(ProductShippingOptions::class)
            ->find($option->getId());

        self::assertEquals(
            $this->getReference('product_unit.milliliter'),
            $updatedOption->getProductUnit()
        );

        self::assertEquals(
            $this->getReference('product-4'),
            $updatedOption->getProduct()
        );

        self::assertEquals(
            $this->getReference('weight_unit.pound')->getCode(),
            $updatedOption->getWeight()->getUnit()->getCode()
        );

        self::assertEquals(
            $this->getReference('length_unit.meter')->getCode(),
            $updatedOption->getDimensions()->getUnit()->getCode()
        );
    }

    private function assertGetSubResource(int $entityId, string $associationName, string $associationId)
    {
        $response = $this->getSubresource([
            'entity'      => 'productshippingoptions',
            'id'          => $entityId,
            'association' => $associationName
        ]);

        $result = json_decode($response->getContent(), true);

        self::assertEquals($associationId, $result['data']['id']);
    }
}
