<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\AddressBundle\Tests\Functional\Api\RestJsonApi\AddressCountryAndRegionTestTrait;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadCountryData;
use Oro\Bundle\AddressBundle\Tests\Functional\DataFixtures\LoadRegionData;
use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderAddressData;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class OrderAddressTest extends RestJsonApiTestCase
{
    use AddressCountryAndRegionTestTrait;

    private const ENTITY_CLASS               = OrderAddress::class;
    private const ENTITY_TYPE                = 'orderaddresses';
    private const CREATE_MIN_REQUEST_DATA    = 'create_address_min.yml';
    private const IS_REGION_REQUIRED         = true;
    private const COUNTRY_REGION_ADDRESS_REF = LoadOrderAddressData::ORDER_ADDRESS_1;

    protected function setUp()
    {
        parent::setUp();

        $this->loadFixtures([
            LoadOrderAddressData::class,
            LoadCountryData::class,
            LoadRegionData::class
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => self::ENTITY_TYPE]
        );

        $this->assertResponseContains('cget_address.yml', $response);
    }

    public function testGet()
    {
        $addressId = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1)->getId();
        $response = $this->get(
            ['entity' => self::ENTITY_TYPE, 'id' => $addressId]
        );

        $this->assertResponseContains('get_address.yml', $response);
    }

    public function testCreate()
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

    public function testTryToCreateWithRequiredDataOnlyAndWithoutOrganizationAndFirstNameAndLastName()
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

    public function testCreateWithRequiredDataOnlyAndOrganization()
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

    public function testCreateWithRequiredDataOnlyAndFirstNameAndLastName()
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

    public function testDelete()
    {
        $addressId = $this->getReference(LoadOrderAddressData::ORDER_ADDRESS_1)->getId();

        $this->delete(
            ['entity' => self::ENTITY_TYPE, 'id' => $addressId]
        );

        $address = $this->getEntityManager()
            ->find(self::ENTITY_CLASS, $addressId);
        self::assertTrue(null === $address);
    }

    public function testDeleteList()
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

    public function testUpdatePhone()
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

    public function testGetCountryRelationship()
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

    public function testGetRegionRelationship()
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

    public function testTryToSetNullCountry()
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
}
