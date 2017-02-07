<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Model;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\DPDBundle\Model\OrderData;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class OrderDataTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testConstructionAndGetters()
    {
        $params = [
            OrderData::FIELD_SHIP_TO_ADDRESS => new OrderAddress(),
            OrderData::FIELD_SHIP_TO_EMAIL => 'an@email',
            OrderData::FIELD_PARCEL_SHOP_ID => 1,
            OrderData::FIELD_SHIP_SERVICE_CODE => 'SERVICE_CODE',
            OrderData::FIELD_WEIGHT => 1.0,
            OrderData::FIELD_CONTENT => 'content string',
            OrderData::FIELD_YOUR_INTERNAL_ID => 'internal id',
            OrderData::FIELD_REFERENCE1 => 'reference 1',
            OrderData::FIELD_REFERENCE2 => 'reference 2',
        ];

        $orderData = new OrderData($params);

        $getterValues = [
            OrderData::FIELD_SHIP_TO_ADDRESS => $orderData->getShipToAddress(),
            OrderData::FIELD_SHIP_TO_EMAIL => $orderData->getShipToEmail(),
            OrderData::FIELD_PARCEL_SHOP_ID => $orderData->getParcelShopId(),
            OrderData::FIELD_SHIP_SERVICE_CODE => $orderData->getShipServiceCode(),
            OrderData::FIELD_WEIGHT => $orderData->getWeight(),
            OrderData::FIELD_CONTENT => $orderData->getContent(),
            OrderData::FIELD_YOUR_INTERNAL_ID => $orderData->getYourInternalId(),
            OrderData::FIELD_REFERENCE1 => $orderData->getReference1(),
            OrderData::FIELD_REFERENCE2 => $orderData->getReference2(),
        ];

        $this->assertEquals($params, $getterValues);
    }

    public function testToArrayNonUS()
    {
        /** @var OrderAddress $shipToAddress */
        $nonUSAddress = (new OrderAddress())
            ->setCountry((new Country('DE'))->setName('Germany'))
            ->setFirstName('FirstName')
            ->setMiddleName('MiddleName')
            ->setLastName('LastName')
            ->setNameSuffix('NameSuffix')
            ->setNamePrefix('NamePrefix')
            ->setOrganization('Organization')
            ->setStreet('Street')
            ->setStreet2('Street2')
            ->setPostalCode('1000')
            ->setCity('City')
            ->setPhone('Phone');

        $params = [
            OrderData::FIELD_SHIP_TO_ADDRESS => $nonUSAddress,
            OrderData::FIELD_SHIP_TO_EMAIL => 'an@email',
            OrderData::FIELD_PARCEL_SHOP_ID => 1,
            OrderData::FIELD_SHIP_SERVICE_CODE => 'SERVICE_CODE',
            OrderData::FIELD_WEIGHT => 1.0,
            OrderData::FIELD_CONTENT => 'content string',
            OrderData::FIELD_YOUR_INTERNAL_ID => 'internal id',
            OrderData::FIELD_REFERENCE1 => 'reference 1',
            OrderData::FIELD_REFERENCE2 => 'reference 2',
        ];

        $orderData = new OrderData($params);

        self::assertEquals(
            [
                'ShipAddress' => [
                    'Company' => 'Organization',
                    'Salutation' => 'NamePrefix',
                    'Name' => substr(
                        'FirstName MiddleName LastName NameSuffix',
                        0,
                        OrderData::DPD_API_SHIP_ADDRESS_NAME_MAX_LENGTH
                    ),
                    'Street' => 'Street',
                    'HouseNo' => 'Street2',
                    'Country' => 'DE',
                    'ZipCode' => '1000',
                    'City' => 'City',
                    'State' => '',
                    'Phone' => 'Phone',
                    'Mail' => 'an@email',
                ],
                'ParcelShopID' => 1,
                'ParcelData' => [
                    'ShipService' => 'SERVICE_CODE',
                    'Weight' => 1.0,
                    'Content' => 'content string',
                    'YourInternalID' => 'internal id',
                    'Reference1' => 'reference 1',
                    'Reference2' => 'reference 2',
                ],
            ],
            $orderData->toArray()
        );
    }

    public function testToArrayUS()
    {
        /** @var OrderAddress $shipToAddress */
        $USAddress = (new OrderAddress())
            ->setCountry((new Country('US'))->setName('United States'))
            ->setRegion((new Region('US-FL'))->setCode('FL'))
            ->setFirstName('FirstName')
            ->setMiddleName('MiddleName')
            ->setLastName('LastName')
            ->setNameSuffix('NameSuffix')
            ->setNamePrefix('NamePrefix')
            ->setOrganization('Organization')
            ->setStreet('Street')
            ->setStreet2('Street2')
            ->setPostalCode('1000')
            ->setCity('City')
            ->setPhone('Phone');

        $params = [
            OrderData::FIELD_SHIP_TO_ADDRESS => $USAddress,
            OrderData::FIELD_SHIP_TO_EMAIL => 'an@email',
            OrderData::FIELD_PARCEL_SHOP_ID => 1,
            OrderData::FIELD_SHIP_SERVICE_CODE => 'SERVICE_CODE',
            OrderData::FIELD_WEIGHT => 1.0,
            OrderData::FIELD_CONTENT => 'content string',
            OrderData::FIELD_YOUR_INTERNAL_ID => 'internal id',
            OrderData::FIELD_REFERENCE1 => 'reference 1',
            OrderData::FIELD_REFERENCE2 => 'reference 2',
        ];

        $orderData = new OrderData($params);

        self::assertEquals(
            [
                'ShipAddress' => [
                    'Company' => 'Organization',
                    'Salutation' => 'NamePrefix',
                    'Name' => substr(
                        'FirstName MiddleName LastName NameSuffix',
                        0,
                        OrderData::DPD_API_SHIP_ADDRESS_NAME_MAX_LENGTH
                    ),
                    'Street' => 'Street',
                    'HouseNo' => 'Street2',
                    'Country' => 'US',
                    'ZipCode' => '1000',
                    'City' => 'City',
                    'State' => 'FL',
                    'Phone' => 'Phone',
                    'Mail' => 'an@email',
                ],
                'ParcelShopID' => 1,
                'ParcelData' => [
                    'ShipService' => 'SERVICE_CODE',
                    'Weight' => 1.0,
                    'Content' => 'content string',
                    'YourInternalID' => 'internal id',
                    'Reference1' => 'reference 1',
                    'Reference2' => 'reference 2',
                ],
            ],
            $orderData->toArray()
        );
    }
}
