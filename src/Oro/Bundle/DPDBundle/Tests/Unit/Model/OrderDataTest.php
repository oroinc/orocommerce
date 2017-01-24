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

    public function testAccessors()
    {
        static::assertPropertyAccessors(
            new OrderData(),
            [
                ['shipToAddress', new OrderAddress()],
                ['shipToEmail', 'an@email'],
                ['parcelShopId', 1],
                ['shipServiceCode', 'SERVICE_CODE'],
                ['weight', 1.0],
                ['content', 'content string'],
                ['yourInternalId', 'internal id'],
                ['reference1', 'reference 1'],
                ['reference2', 'reference 2'],
            ]
        );
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
            ->setPostalCode('PostalCode')
            ->setCity('City')
            ->setPhone('Phone');

        $orderData = (new OrderData())
            ->setShipToAddress($nonUSAddress)
            ->setShipToEmail('an@email')
            ->setParcelShopId(1)
            ->setShipServiceCode('SERVICE_CODE')
            ->setWeight(1.0)
            ->setContent('content string')
            ->setYourInternalId('internal id')
            ->setReference1('reference 1')
            ->setReference2('reference 2');

        self::assertEquals(
            [
                'ShipAddress' => [
                    'Company' => 'Organization',
                    'Salutation' => 'NamePrefix',
                    'Name' => 'FirstName MiddleName LastName NameSuffix',
                    'Street' => 'Street',
                    'HouseNo' => 'Street2',
                    'Country' => 'DE',
                    'ZipCode' => 'PostalCode',
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
        $nonUSAddress = (new OrderAddress())
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
            ->setPostalCode('PostalCode')
            ->setCity('City')
            ->setPhone('Phone');

        $orderData = (new OrderData())
            ->setShipToAddress($nonUSAddress)
            ->setShipToEmail('an@email')
            ->setParcelShopId(1)
            ->setShipServiceCode('SERVICE_CODE')
            ->setWeight(1.0)
            ->setContent('content string')
            ->setYourInternalId('internal id')
            ->setReference1('reference 1')
            ->setReference2('reference 2');

        self::assertEquals(
            [
                'ShipAddress' => [
                    'Company' => 'Organization',
                    'Salutation' => 'NamePrefix',
                    'Name' => 'FirstName MiddleName LastName NameSuffix',
                    'Street' => 'Street',
                    'HouseNo' => 'Street2',
                    'Country' => 'US',
                    'ZipCode' => 'PostalCode',
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
