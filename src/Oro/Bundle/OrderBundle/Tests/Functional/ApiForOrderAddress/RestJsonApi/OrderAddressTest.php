<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiForOrderAddress\RestJsonApi;

use Oro\Bundle\AddressBundle\Tests\Functional\Api\RestJsonApi\AddressCountryAndRegionTestTrait;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadCountryData;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadRegionData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerAddresses;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserAddresses;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderAddressData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderLineItemData;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrders;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class OrderAddressTest extends RestJsonApiTestCase
{
    use AddressCountryAndRegionTestTrait;

    private const ENTITY_CLASS = OrderAddress::class;
    private const ENTITY_TYPE = 'orderaddresses';
    private const CREATE_MIN_REQUEST_DATA = 'create_address_min.yml';
    private const IS_REGION_REQUIRED = true;
    private const COUNTRY_REGION_ADDRESS_REF = LoadOrderAddressData::ORDER_ADDRESS_1;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadOrderAddressData::class,
            LoadOrderLineItemData::class,
            LoadCountryData::class,
            LoadRegionData::class,
            LoadCustomerAddresses::class,
            LoadCustomerUserAddresses::class
        ]);
    }

    private function getOrder(int $orderId): Order
    {
        return $this->getEntityManager()->find(Order::class, $orderId);
    }

    public function testGetList(): void
    {
        $response = $this->cget(['entity' => self::ENTITY_TYPE]);

        $this->assertResponseContains('cget_address.yml', $response);
    }

    public function testGetListFilterByCountry(): void
    {
        $response = $this->cget(
            ['entity' => self::ENTITY_TYPE],
            ['filter' => ['country' => ['neq' => '<toString(@order_address.office->country->iso2Code)>']]]
        );

        $this->assertResponseContains(['data' => []], $response);
    }

    public function testGetListFilterByRegion(): void
    {
        $response = $this->cget(
            ['entity' => self::ENTITY_TYPE],
            ['filter' => ['region' => '<toString(@order_address.office->region->combinedCode)>']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => self::ENTITY_TYPE, 'id' => '<toString(@order_address.office->id)>'],
                    ['type' => self::ENTITY_TYPE, 'id' => '<toString(@order_address.order2.billing->id)>']
                ]
            ],
            $response
        );
    }

    public function testGetListFilterByCustomRegion(): void
    {
        $response = $this->cget(
            ['entity' => self::ENTITY_TYPE],
            ['filter' => ['customRegion' => ['exists' => false]]]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => self::ENTITY_TYPE, 'id' => '<toString(@order_address.office->id)>'],
                    ['type' => self::ENTITY_TYPE, 'id' => '<toString(@order_address.warehouse->id)>'],
                    ['type' => self::ENTITY_TYPE, 'id' => '<toString(@order_address.order2.billing->id)>'],
                    ['type' => self::ENTITY_TYPE, 'id' => '<toString(@order_address.order2.shipping->id)>']
                ]
            ],
            $response
        );
    }

    public function testGet(): void
    {
        $addressId = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1)->getId();
        $response = $this->get(
            ['entity' => self::ENTITY_TYPE, 'id' => $addressId]
        );

        $this->assertResponseContains('get_address.yml', $response);
    }

    public function testCreate(): void
    {
        $countryId = $this->getReference('country.usa')->getIso2Code();
        $regionId = $this->getReference('region.usny')->getCombinedCode();

        $response = $this->post(
            ['entity' => self::ENTITY_TYPE],
            'create_address.yml'
        );

        $addressId = (int)$this->getResourceId($response);
        $responseContent = $this->updateResponseContent('create_address.yml', $response);
        $this->assertResponseContains($responseContent, $response);

        /** @var OrderAddress $address */
        $address = $this->getEntityManager()
            ->find(self::ENTITY_CLASS, $addressId);
        self::assertNotNull($address);
        self::assertEquals('New Address', $address->getLabel());
        self::assertFalse($address->isFromExternalSource());
        self::assertEquals('777-777-777', $address->getPhone());
        self::assertEquals('1215 Caldwell Road', $address->getStreet());
        self::assertEquals('Street 2', $address->getStreet2());
        self::assertEquals('Rochester', $address->getCity());
        self::assertEquals('14608', $address->getPostalCode());
        self::assertEquals('test organization', $address->getOrganization());
        self::assertEquals('Mr.', $address->getNamePrefix());
        self::assertEquals('M.D.', $address->getNameSuffix());
        self::assertEquals('John', $address->getFirstName());
        self::assertEquals('Edgar', $address->getMiddleName());
        self::assertEquals('Doo', $address->getLastName());
        self::assertEquals($countryId, $address->getCountry()->getIso2Code());
        self::assertEquals($regionId, $address->getRegion()->getCombinedCode());
    }

    public function testTryToCreateWithRequiredDataOnlyAndWithoutOrganizationAndFirstNameAndLastName(): void
    {
        $data = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        unset($data['data']['attributes']['organization']);
        $response = $this->post(
            ['entity' => self::ENTITY_TYPE],
            $data,
            [],
            false
        );

        $this->assertResponseValidationErrors(
            [
                [
                    'title'  => 'name or organization constraint',
                    'detail' => 'Organization or First Name and Last Name should not be blank.',
                    'source' => ['pointer' => '/data/attributes/organization']
                ],
                [
                    'title'  => 'name or organization constraint',
                    'detail' => 'First Name and Last Name or Organization should not be blank.',
                    'source' => ['pointer' => '/data/attributes/firstName']
                ],
                [
                    'title'  => 'name or organization constraint',
                    'detail' => 'Last Name and First Name or Organization should not be blank.',
                    'source' => ['pointer' => '/data/attributes/lastName']
                ]
            ],
            $response
        );
    }

    public function testCreateWithRequiredDataOnlyAndOrganization(): void
    {
        $countryId = $this->getReference('country.usa')->getIso2Code();
        $regionId = $this->getReference('region.usny')->getCombinedCode();

        $data = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        $response = $this->post(
            ['entity' => self::ENTITY_TYPE],
            $data
        );

        $addressId = (int)$this->getResourceId($response);
        $responseContent = $data;
        $responseContent['data']['attributes']['label'] = null;
        $responseContent['data']['attributes']['fromExternalSource'] = false;
        $responseContent['data']['attributes']['phone'] = null;
        $responseContent['data']['attributes']['street2'] = null;
        $responseContent['data']['attributes']['namePrefix'] = null;
        $responseContent['data']['attributes']['nameSuffix'] = null;
        $responseContent['data']['attributes']['firstName'] = null;
        $responseContent['data']['attributes']['middleName'] = null;
        $responseContent['data']['attributes']['lastName'] = null;
        $this->assertResponseContains($responseContent, $response);

        /** @var OrderAddress $address */
        $address = $this->getEntityManager()
            ->find(self::ENTITY_CLASS, $addressId);
        self::assertNotNull($address);
        self::assertNull($address->getLabel());
        self::assertFalse($address->isFromExternalSource());
        self::assertNull($address->getPhone());
        self::assertEquals('1215 Caldwell Road', $address->getStreet());
        self::assertNull($address->getStreet2());
        self::assertEquals('Rochester', $address->getCity());
        self::assertEquals('14608', $address->getPostalCode());
        self::assertEquals('test organization', $address->getOrganization());
        self::assertNull($address->getNamePrefix());
        self::assertNull($address->getNameSuffix());
        self::assertNull($address->getFirstName());
        self::assertNull($address->getMiddleName());
        self::assertNull($address->getLastName());
        self::assertEquals($countryId, $address->getCountry()->getIso2Code());
        self::assertEquals($regionId, $address->getRegion()->getCombinedCode());
    }

    public function testCreateWithRequiredDataOnlyAndFirstNameAndLastName(): void
    {
        $countryId = $this->getReference('country.usa')->getIso2Code();
        $regionId = $this->getReference('region.usny')->getCombinedCode();

        $data = $this->getRequestData(self::CREATE_MIN_REQUEST_DATA);
        unset($data['data']['attributes']['organization']);
        $data['data']['attributes']['firstName'] = 'John';
        $data['data']['attributes']['lastName'] = 'Doo';
        $response = $this->post(
            ['entity' => self::ENTITY_TYPE],
            $data
        );

        $addressId = (int)$this->getResourceId($response);
        $responseContent = $data;
        $responseContent['data']['attributes']['label'] = null;
        $responseContent['data']['attributes']['fromExternalSource'] = false;
        $responseContent['data']['attributes']['phone'] = null;
        $responseContent['data']['attributes']['street2'] = null;
        $responseContent['data']['attributes']['organization'] = null;
        $responseContent['data']['attributes']['namePrefix'] = null;
        $responseContent['data']['attributes']['nameSuffix'] = null;
        $responseContent['data']['attributes']['middleName'] = null;
        $this->assertResponseContains($responseContent, $response);

        /** @var OrderAddress $address */
        $address = $this->getEntityManager()
            ->find(self::ENTITY_CLASS, $addressId);
        self::assertNotNull($address);
        self::assertNull($address->getLabel());
        self::assertFalse($address->isFromExternalSource());
        self::assertNull($address->getPhone());
        self::assertEquals('1215 Caldwell Road', $address->getStreet());
        self::assertNull($address->getStreet2());
        self::assertEquals('Rochester', $address->getCity());
        self::assertEquals('14608', $address->getPostalCode());
        self::assertNull($address->getOrganization());
        self::assertNull($address->getNamePrefix());
        self::assertNull($address->getNameSuffix());
        self::assertEquals('John', $address->getFirstName());
        self::assertNull($address->getMiddleName());
        self::assertEquals('Doo', $address->getLastName());
        self::assertEquals($countryId, $address->getCountry()->getIso2Code());
        self::assertEquals($regionId, $address->getRegion()->getCombinedCode());
    }

    public function testCreateBasedOnCustomerAddress(): void
    {
        $customerAddressId = $this->getReference('customer.level_1.address_1')->getId();

        $data = [
            'data' => [
                'type' => self::ENTITY_TYPE,
                'relationships' => [
                    'customerAddress' => [
                        'data' => ['type' => 'customeraddresses', 'id' => (string)$customerAddressId]
                    ]
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => self::ENTITY_TYPE],
            $data
        );

        $addressId = (int)$this->getResourceId($response);
        /** @var OrderAddress $address */
        $address = $this->getEntityManager()->find(self::ENTITY_CLASS, $addressId);
        self::assertTrue(null !== $address);
        /** @var CustomerAddress $customerAddress */
        $customerAddress = $this->getEntityManager()->find(CustomerAddress::class, $customerAddressId);

        $expectedData = array_merge($data, [
            'data' => [
                'id' => (string)$addressId,
                'attributes' => [
                    'createdAt' => $address->getCreated()->format('Y-m-d\TH:i:s\Z'),
                    'updatedAt' => $address->getUpdated()->format('Y-m-d\TH:i:s\Z'),
                    'phone' => $customerAddress->getPhone(),
                    'label' => $customerAddress->getLabel(),
                    'street' => $customerAddress->getStreet(),
                    'street2' => $customerAddress->getStreet2(),
                    'city' => $customerAddress->getCity(),
                    'postalCode' => $customerAddress->getPostalCode(),
                    'organization' => $customerAddress->getOrganization(),
                    'customRegion' => $customerAddress->getRegionText(),
                    'namePrefix' => $customerAddress->getNamePrefix(),
                    'firstName' => $customerAddress->getFirstName(),
                    'middleName' => $customerAddress->getMiddleName(),
                    'lastName' => $customerAddress->getLastName(),
                    'nameSuffix' => $customerAddress->getNameSuffix(),
                    'validatedAt' => $customerAddress->getValidatedAt()
                ],
                'relationships' => [
                    'country' => [
                        'data' => [
                            'type' => 'countries',
                            'id' => $customerAddress->getCountry()->getIso2Code()
                        ]
                    ],
                    'region' => [
                        'data' => [
                            'type' => 'regions',
                            'id' => $customerAddress->getRegion()->getCombinedCode()
                        ]
                    ],
                    'customerUserAddress' => [
                        'data' => null
                    ]
                ]
            ]
        ]);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testCreateBasedOnCustomerUserAddress(): void
    {
        $customerUserAddressId = $this->getReference('other.user@test.com.address_1')->getId();

        $data = [
            'data' => [
                'type' => self::ENTITY_TYPE,
                'relationships' => [
                    'customerUserAddress' => [
                        'data' => ['type' => 'customeruseraddresses', 'id' => (string)$customerUserAddressId]
                    ]
                ]
            ]
        ];
        $response = $this->post(
            ['entity' => self::ENTITY_TYPE],
            $data
        );

        $addressId = (int)$this->getResourceId($response);
        /** @var OrderAddress $address */
        $address = $this->getEntityManager()->find(self::ENTITY_CLASS, $addressId);
        self::assertTrue(null !== $address);
        /** @var CustomerUserAddress $customerUserAddress */
        $customerUserAddress = $this->getEntityManager()->find(CustomerUserAddress::class, $customerUserAddressId);

        $expectedData = array_merge($data, [
            'data' => [
                'id' => (string)$addressId,
                'attributes' => [
                    'createdAt' => $address->getCreated()->format('Y-m-d\TH:i:s\Z'),
                    'updatedAt' => $address->getUpdated()->format('Y-m-d\TH:i:s\Z'),
                    'phone' => $customerUserAddress->getPhone(),
                    'label' => $customerUserAddress->getLabel(),
                    'street' => $customerUserAddress->getStreet(),
                    'street2' => $customerUserAddress->getStreet2(),
                    'city' => $customerUserAddress->getCity(),
                    'postalCode' => $customerUserAddress->getPostalCode(),
                    'organization' => $customerUserAddress->getOrganization(),
                    'customRegion' => $customerUserAddress->getRegionText(),
                    'namePrefix' => $customerUserAddress->getNamePrefix(),
                    'firstName' => $customerUserAddress->getFirstName(),
                    'middleName' => $customerUserAddress->getMiddleName(),
                    'lastName' => $customerUserAddress->getLastName(),
                    'nameSuffix' => $customerUserAddress->getNameSuffix(),
                    'validatedAt' => $customerUserAddress->getValidatedAt()
                ],
                'relationships' => [
                    'country' => [
                        'data' => [
                            'type' => 'countries',
                            'id' => $customerUserAddress->getCountry()->getIso2Code()
                        ]
                    ],
                    'region' => [
                        'data' => [
                            'type' => 'regions',
                            'id' => $customerUserAddress->getRegion()->getCombinedCode()
                        ]
                    ],
                    'customerAddress' => [
                        'data' => null
                    ]
                ]
            ]
        ]);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testDelete(): void
    {
        $addressId = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1)->getId();

        $this->delete(
            ['entity' => self::ENTITY_TYPE, 'id' => $addressId]
        );

        $address = $this->getEntityManager()
            ->find(self::ENTITY_CLASS, $addressId);
        self::assertTrue(null === $address);
    }

    public function testDeleteList(): void
    {
        $addressId = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1)->getId();

        $this->cdelete(
            ['entity' => self::ENTITY_TYPE],
            ['filter' => ['id' => $addressId]]
        );

        $address = $this->getEntityManager()
            ->find(self::ENTITY_CLASS, $addressId);
        self::assertTrue(null === $address);
    }

    public function testUpdatePhone(): void
    {
        $addressId = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1)->getId();

        $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => $addressId],
            [
                'data' => [
                    'type'       => self::ENTITY_TYPE,
                    'id'         => (string)$addressId,
                    'attributes' => [
                        'phone' => '111-111-111'
                    ]
                ]
            ]
        );

        /** @var OrderAddress $address */
        $address = $this->getEntityManager()
            ->find(self::ENTITY_CLASS, $addressId);
        self::assertSame('111-111-111', $address->getPhone());
    }

    public function testGetCountryRelationship(): void
    {
        $addressId = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1)->getId();

        $response = $this->getRelationship(
            ['entity' => self::ENTITY_TYPE, 'id' => $addressId, 'association' => 'country']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'countries', 'id' => 'US']],
            $response
        );
    }

    public function testGetRegionRelationship(): void
    {
        $addressId = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1)->getId();

        $response = $this->getRelationship(
            ['entity' => self::ENTITY_TYPE, 'id' => $addressId, 'association' => 'region']
        );

        $this->assertResponseContains(
            ['data' => ['type' => 'regions', 'id' => 'US-NY']],
            $response
        );
    }

    public function testTryToSetNullCountry(): void
    {
        $addressId = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1)->getId();
        $data = [
            'data' => [
                'type'          => self::ENTITY_TYPE,
                'id'            => (string)$addressId,
                'relationships' => [
                    'country' => [
                        'data' => null
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => $addressId],
            $data,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'not blank constraint',
                'detail' => 'This value should not be blank.',
                'source' => ['pointer' => '/data/relationships/country/data']
            ],
            $response
        );
    }

    public function testUpdateOrderShippingAddressShouldRecalculateOrderTotals(): void
    {
        $orderId = $this->getReference(LoadOrders::ORDER_2)->getId();
        $shippingAddressId = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_4)->getId();

        // guard
        self::assertSame('1234.0000', $this->getOrder($orderId)->getTotal());

        $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => (string)$shippingAddressId],
            [
                'data' => [
                    'type'          => self::ENTITY_TYPE,
                    'id'            => (string)$shippingAddressId,
                    'relationships' => [
                        'region' => ['data' => ['type' => 'regions', 'id' => 'US-CA']]
                    ]
                ]
            ]
        );

        self::assertSame('1000.0000', $this->getOrder($orderId)->getTotal());
    }

    public function testUpdateOrderBillingAddressShouldNotRecalculateOrderTotals(): void
    {
        $orderId = $this->getReference(LoadOrders::ORDER_2)->getId();
        $billingAddressId = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_3)->getId();

        // guard
        self::assertSame('1234.0000', $this->getOrder($orderId)->getTotal());

        $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => (string)$billingAddressId],
            [
                'data' => [
                    'type'          => self::ENTITY_TYPE,
                    'id'            => (string)$billingAddressId,
                    'relationships' => [
                        'region' => ['data' => ['type' => 'regions', 'id' => 'US-CA']]
                    ]
                ]
            ]
        );

        self::assertSame('1234.0000', $this->getOrder($orderId)->getTotal());
    }


    public function testUpdateBasedOnCustomerAddress(): void
    {
        $addressId = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1)->getId();
        $customerAddressId = $this->getReference('customer.level_1.address_1')->getId();

        $data = [
            'data' => [
                'type' => self::ENTITY_TYPE,
                'id' => (string)$addressId,
                'relationships' => [
                    'customerAddress' => [
                        'data' => ['type' => 'customeraddresses', 'id' => (string)$customerAddressId]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => (string)$addressId],
            $data
        );

        /** @var OrderAddress $address */
        $address = $this->getEntityManager()->find(self::ENTITY_CLASS, $addressId);
        self::assertTrue(null !== $address);
        /** @var CustomerAddress $customerAddress */
        $customerAddress = $this->getEntityManager()->find(CustomerAddress::class, $customerAddressId);

        $expectedData = array_merge($data, [
            'data' => [
                'attributes' => [
                    'createdAt' => $address->getCreated()->format('Y-m-d\TH:i:s\Z'),
                    'updatedAt' => $address->getUpdated()->format('Y-m-d\TH:i:s\Z'),
                    'phone' => $customerAddress->getPhone(),
                    'label' => $customerAddress->getLabel(),
                    'street' => $customerAddress->getStreet(),
                    'street2' => $customerAddress->getStreet2(),
                    'city' => $customerAddress->getCity(),
                    'postalCode' => $customerAddress->getPostalCode(),
                    'organization' => $customerAddress->getOrganization(),
                    'customRegion' => $customerAddress->getRegionText(),
                    'namePrefix' => $customerAddress->getNamePrefix(),
                    'firstName' => $customerAddress->getFirstName(),
                    'middleName' => $customerAddress->getMiddleName(),
                    'lastName' => $customerAddress->getLastName(),
                    'nameSuffix' => $customerAddress->getNameSuffix(),
                    'validatedAt' => $customerAddress->getValidatedAt()
                ],
                'relationships' => [
                    'country' => [
                        'data' => [
                            'type' => 'countries',
                            'id' => $customerAddress->getCountry()->getIso2Code()
                        ]
                    ],
                    'region' => [
                        'data' => [
                            'type' => 'regions',
                            'id' => $customerAddress->getRegion()->getCombinedCode()
                        ]
                    ],
                    'customerUserAddress' => [
                        'data' => null
                    ]
                ]
            ]
        ]);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testUpdateBasedOnCustomerUserAddress(): void
    {
        $addressId = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1)->getId();
        $customerUserAddressId = $this->getReference('other.user@test.com.address_1')->getId();

        $data = [
            'data' => [
                'type' => self::ENTITY_TYPE,
                'id' => (string)$addressId,
                'relationships' => [
                    'customerUserAddress' => [
                        'data' => ['type' => 'customeruseraddresses', 'id' => (string)$customerUserAddressId]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => self::ENTITY_TYPE, 'id' => (string)$addressId],
            $data
        );

        /** @var OrderAddress $address */
        $address = $this->getEntityManager()->find(self::ENTITY_CLASS, $addressId);
        self::assertTrue(null !== $address);
        /** @var CustomerUserAddress $customerUserAddress */
        $customerUserAddress = $this->getEntityManager()->find(CustomerUserAddress::class, $customerUserAddressId);

        $expectedData = array_merge($data, [
            'data' => [
                'attributes' => [
                    'createdAt' => $address->getCreated()->format('Y-m-d\TH:i:s\Z'),
                    'updatedAt' => $address->getUpdated()->format('Y-m-d\TH:i:s\Z'),
                    'phone' => $customerUserAddress->getPhone(),
                    'label' => $customerUserAddress->getLabel(),
                    'street' => $customerUserAddress->getStreet(),
                    'street2' => $customerUserAddress->getStreet2(),
                    'city' => $customerUserAddress->getCity(),
                    'postalCode' => $customerUserAddress->getPostalCode(),
                    'organization' => $customerUserAddress->getOrganization(),
                    'customRegion' => $customerUserAddress->getRegionText(),
                    'namePrefix' => $customerUserAddress->getNamePrefix(),
                    'firstName' => $customerUserAddress->getFirstName(),
                    'middleName' => $customerUserAddress->getMiddleName(),
                    'lastName' => $customerUserAddress->getLastName(),
                    'nameSuffix' => $customerUserAddress->getNameSuffix(),
                    'validatedAt' => $customerUserAddress->getValidatedAt()
                ],
                'relationships' => [
                    'country' => [
                        'data' => [
                            'type' => 'countries',
                            'id' => $customerUserAddress->getCountry()->getIso2Code()
                        ]
                    ],
                    'region' => [
                        'data' => [
                            'type' => 'regions',
                            'id' => $customerUserAddress->getRegion()->getCombinedCode()
                        ]
                    ],
                    'customerAddress' => [
                        'data' => null
                    ]
                ]
            ]
        ]);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testTryToUpdateCustomerAddressViaRelationship(): void
    {
        $addressId = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1)->getId();

        $response = $this->patchRelationship(
            ['entity' => self::ENTITY_TYPE, 'id' => (string)$addressId, 'association' => 'customerAddress'],
            [
                'data' => [
                    'type' => 'customeraddresses',
                    'id' => '<toString(@customer.level_1.address_1->id)>'
                ]
            ],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToUpdateCustomerUserAddressViaRelationship(): void
    {
        $addressId = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1)->getId();

        $response = $this->patchRelationship(
            ['entity' => self::ENTITY_TYPE, 'id' => (string)$addressId, 'association' => 'customerUserAddress'],
            [
                'data' => [
                    'type' => 'customeruseraddresses',
                    'id' => '<toString(@other.user@test.com.address_1->id)>'
                ]
            ],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
