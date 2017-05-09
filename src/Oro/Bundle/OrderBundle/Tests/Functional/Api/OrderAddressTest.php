<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadCountryData;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadRegionData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderAddressData;

class OrderAddressTest extends RestJsonApiTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            LoadOrderAddressData::class,
            LoadCountryData::class,
            LoadRegionData::class,
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(['entity' => 'orderaddresses']);

        $this->assertResponseContains('address_get_list.yml', $response);
    }

    public function testGet()
    {
        /** @var OrderAddress $orderAddress */
        $orderAddress = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1);

        $response = $this->get(
            ['entity' => 'orderaddresses', 'id' => $orderAddress->getId()]
        );

        $this->assertResponseContains('address_get.yml', $response);
    }

    public function testGetCountryRelationship()
    {
        /** @var OrderAddress $orderAddress */
        $orderAddress = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1);

        $response = $this->getRelationship(
            ['entity' => 'orderaddresses', 'id' => $orderAddress->getId(), 'association' => 'country']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'countries', 'id' => 'US']],
            $response
        );
    }

    public function testGetRegionRelationship()
    {
        /** @var OrderAddress $orderAddress */
        $orderAddress = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_2);

        $response = $this->getRelationship(
            ['entity' => 'orderaddresses', 'id' => $orderAddress->getId(), 'association' => 'region']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'regions', 'id' => 'US-IN']],
            $response
        );
    }

    public function testCreate()
    {
        $this->post(
            ['entity' => 'orderaddresses'],
            'address_create.yml'
        );

        /** @var OrderAddress $orderAddress */
        $orderAddress = $this->getEntityManager()
            ->getRepository(OrderAddress::class)
            ->findOneBy(['phone' => '777-777-777']);

        self::assertSame('1215 Caldwell Road', $orderAddress->getStreet());
        self::assertSame('Rochester', $orderAddress->getCity());
        self::assertSame('US', $orderAddress->getCountryIso2());
        self::assertSame('US-NY', $orderAddress->getRegion()->getCombinedCode());
    }

    public function testUpdatePhone()
    {
        /** @var OrderAddress $orderAddress */
        $orderAddress = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1);

        $this->patch(
            ['entity' => 'orderaddresses', 'id' => $orderAddress->getId()],
            [
                'data' => [
                    'type' => 'orderaddresses',
                    'id' => (string)$orderAddress->getId(),
                    'attributes' => [
                        'phone' => '111-111-111',
                    ],
                ],
            ]
        );

        /** @var OrderAddress $updatedOrderAddress */
        $updatedOrderAddress = $this->getEntityManager()
            ->getRepository(OrderAddress::class)
            ->find($orderAddress->getId());

        self::assertSame('111-111-111', $updatedOrderAddress->getPhone());
    }

    public function testUpdateCountryRelationship()
    {
        /** @var OrderAddress $orderAddress */
        $orderAddress = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1);
        /** @var Country $country */
        $country = $this->getReference(LoadCountryData::COUNTRY_MEXICO);

        $this->patchRelationship(
            ['entity' => 'orderaddresses', 'id' => $orderAddress->getId(), 'association' => 'country'],
            [
                'data' => [
                    'type' => $this->getEntityType(Country::class),
                    'id' => $country->getIso2Code(),
                ],
            ]
        );

        /** @var OrderAddress $updatedOrderAddress */
        $updatedOrderAddress = $this->getEntityManager()
            ->getRepository(OrderAddress::class)
            ->find($orderAddress->getId());

        self::assertEquals($country->getIso2Code(), $updatedOrderAddress->getCountryIso2());
    }

    public function testUpdateRegionRelationship()
    {
        /** @var OrderAddress $orderAddress */
        $orderAddress = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1);
        /** @var Region $region */
        $region = $this->getReference(LoadRegionData::REGION_AD_07);

        $this->patchRelationship(
            ['entity' => 'orderaddresses', 'id' => $orderAddress->getId(), 'association' => 'region'],
            [
                'data' => [
                    'type' => $this->getEntityType(Region::class),
                    'id' => $region->getCombinedCode(),
                ],
            ]
        );

        /** @var OrderAddress $updatedOrderAddress */
        $updatedOrderAddress = $this->getEntityManager()
            ->getRepository(OrderAddress::class)
            ->find($orderAddress->getId());

        self::assertEquals($region->getCombinedCode(), $updatedOrderAddress->getRegion()->getCombinedCode());
    }

    public function testDeleteByFilter()
    {
        /** @var OrderAddress $orderAddress */
        $orderAddress = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1);
        $orderAddressId = $orderAddress->getId();

        $this->cdelete(
            ['entity' => 'orderaddresses'],
            ['filter' => ['id' => $orderAddressId]]
        );

        $removedOrderAddress = $this->getEntityManager()
            ->getRepository(OrderAddress::class)
            ->find($orderAddressId);

        self::assertNull($removedOrderAddress);
    }
}
