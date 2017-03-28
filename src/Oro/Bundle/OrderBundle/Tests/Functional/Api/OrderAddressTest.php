<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api;

use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadCountryData;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadRegionData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderAddressData;
use Symfony\Component\HttpFoundation\Response;

class OrderAddressTest extends RestJsonApiTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures(
            [
                LoadOrderAddressData::class,
                LoadCountryData::class,
                LoadRegionData::class,
            ]
        );
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => $this->getEntityType(OrderAddress::class)]);

        $this->assertResponseContains(__DIR__ . '/responses/address/get_addresses.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get(
            [
                'entity' => $this->getEntityType(OrderAddress::class),
                'id' => '<toString(@order_address.office->id)>',
            ]
        );
        $this->assertResponseContains(__DIR__ . '/responses/address/get_address.yml', $response);
    }

    public function testGetCountryRelationship()
    {
        /** @var OrderAddress $orderAddress */
        $orderAddress = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1);

        $uri = $this->getUrl(
            'oro_rest_api_get_relationship',
            [
                'entity' => $this->getEntityType(OrderAddress::class),
                'id' => $orderAddress->getId(),
                'association' => 'country',
            ]
        );
        $response = $this->request('GET', $uri);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);

        $expected = [
            'data' => [
                'type' => $this->getEntityType(Country::class),
                'id' => 'US',
            ],
        ];

        static::assertEquals($expected, $content);
    }

    public function testGetRegionRelationship()
    {
        /** @var OrderAddress $orderAddress */
        $orderAddress = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_2);

        $uri = $this->getUrl(
            'oro_rest_api_get_relationship',
            [
                'entity' => $this->getEntityType(OrderAddress::class),
                'id' => $orderAddress->getId(),
                'association' => 'region',
            ]
        );
        $response = $this->request('GET', $uri);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $content = json_decode($response->getContent(), true);

        $expected = [
            'data' => [
                'type' => $this->getEntityType(Region::class),
                'id' => 'US-IN',
            ],
        ];

        static::assertEquals($expected, $content);
    }

    public function testCreate()
    {
        $this->post(
            ['entity' => $this->getEntityType(OrderAddress::class)],
            __DIR__ . '/responses/address/create_address.yml'
        );

        /** @var OrderAddress $orderAddress */
        $orderAddress = $this->getManager()
            ->getRepository(OrderAddress::class)
            ->findOneBy(['phone' => '777-777-777']);

        static::assertSame('1215 Caldwell Road', $orderAddress->getStreet());
        static::assertSame('Rochester', $orderAddress->getCity());
        static::assertSame('US', $orderAddress->getCountryIso2());
        static::assertSame('US-NY', $orderAddress->getRegion()->getCombinedCode());

        $this->getManager()->remove($orderAddress);
        $this->getManager()->flush();
        $this->getManager()->clear();
    }

    /**
     * @return ObjectManager
     */
    private function getManager()
    {
        return static::getContainer()->get('doctrine')->getManager();
    }

    public function testUpdatePhone()
    {
        /** @var OrderAddress $orderAddress */
        $orderAddress = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1);

        $requestData = [
            'data' => [
                'type' => $this->getEntityType(OrderAddress::class),
                'id' => (string)$orderAddress->getId(),
                'attributes' => [
                    'phone' => '111-111-111',
                ],
            ],
        ];

        $uri = $this->getUrl(
            'oro_rest_api_patch',
            [
                'entity' => $this->getEntityType(OrderAddress::class),
                'id' => $orderAddress->getId(),
            ]
        );
        $response = $this->request('PATCH', $uri, $requestData);

        /** @var OrderAddress $updatedOrderAddress */
        $updatedOrderAddress = $this->getManager()
            ->getRepository(OrderAddress::class)
            ->find($orderAddress->getId());

        static::assertSame(Response::HTTP_OK, $response->getStatusCode());
        static::assertSame('111-111-111', $updatedOrderAddress->getPhone());
    }

    public function testUpdateCountryRelationship()
    {
        /** @var OrderAddress $orderAddress */
        $orderAddress = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1);

        /** @var Country $country */
        $country = $this->getReference(LoadCountryData::COUNTRY_MEXICO);

        $uri = $this->getUrl(
            'oro_rest_api_patch_relationship',
            [
                'entity' => $this->getEntityType(OrderAddress::class),
                'id' => $orderAddress->getId(),
                'association' => 'country',
            ]
        );
        $data = [
            'data' => [
                'type' => $this->getEntityType(Country::class),
                'id' => $country->getIso2Code(),
            ],
        ];
        $response = $this->request('PATCH', $uri, $data);

        /** @var OrderAddress $updatedOrderAddress */
        $updatedOrderAddress = $this->getManager()
            ->getRepository(OrderAddress::class)
            ->find($orderAddress->getId());

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        static::assertEquals($country->getIso2Code(), $updatedOrderAddress->getCountryIso2());
    }

    public function testUpdateRegionRelationship()
    {
        /** @var OrderAddress $orderAddress */
        $orderAddress = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1);

        /** @var Region $region */
        $region = $this->getReference(LoadRegionData::REGION_AD_07);

        $uri = $this->getUrl(
            'oro_rest_api_patch_relationship',
            [
                'entity' => $this->getEntityType(OrderAddress::class),
                'id' => $orderAddress->getId(),
                'association' => 'region',
            ]
        );
        $data = [
            'data' => [
                'type' => $this->getEntityType(Region::class),
                'id' => $region->getCombinedCode(),
            ],
        ];
        $response = $this->request('PATCH', $uri, $data);

        /** @var OrderAddress $updatedOrderAddress */
        $updatedOrderAddress = $this->getManager()
            ->getRepository(OrderAddress::class)
            ->find($orderAddress->getId());

        static::assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        static::assertEquals($region->getCombinedCode(), $updatedOrderAddress->getRegion()->getCombinedCode());
    }

    public function testDeleteByFilter()
    {
        /** @var OrderAddress $orderAddress */
        $orderAddress = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1);
        $orderAddressId = $orderAddress->getId();

        $uri = $this->getUrl(
            'oro_rest_api_cget',
            ['entity' => $this->getEntityType(OrderAddress::class)]
        );
        $response = $this->request(
            'DELETE',
            $uri,
            ['filter' => ['id' => $orderAddressId]]
        );

        $this->getManager()->clear();

        $removedOrderAddress = $this->getManager()
            ->getRepository(OrderAddress::class)
            ->find($orderAddressId);

        static::assertResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);
        static::assertNull($removedOrderAddress);
    }
}
