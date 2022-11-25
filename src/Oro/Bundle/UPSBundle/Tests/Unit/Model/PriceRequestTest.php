<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Model;

use Oro\Bundle\LocaleBundle\Tests\Unit\Formatter\Stubs\AddressStub;
use Oro\Bundle\UPSBundle\Model\Package;
use Oro\Bundle\UPSBundle\Model\PriceRequest;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class PriceRequestTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    private const PACKAGING_TYPE_CODE = '02';
    private const WEIGHT_CODE = 'LB';
    private const WEIGHT = '4';
    private const USERNAME = 'user';
    private const PASSWORD = 'password';
    private const ACCESS_LICENSE_NUMBER = '123';
    private const REQUEST_OPTION = 'Rate';
    private const SERVICE_CODE = '02';
    private const SERVICE_DESCRIPTION = 'Service Code Description';
    private const SHIPPER_NAME = 'Shipper Name';
    private const SHIPPER_NUMBER = '123';
    private const SHIP_FROM_NAME = 'Ship From Name';
    private const SHIP_TO_NAME = 'Ship To Name';

    /** @var array */
    private $resultArray;

    /** @var PriceRequest */
    private $model;

    protected function setUp(): void
    {
        $this->model = new PriceRequest();
    }

    public function testGettersAndSetters()
    {
        self::assertPropertyAccessors(
            $this->model,
            [
                ['username', self::USERNAME],
                ['password', self::PASSWORD],
                ['accessLicenseNumber', self::ACCESS_LICENSE_NUMBER],
                ['requestOption', self::REQUEST_OPTION],
                ['serviceDescription', self::SERVICE_DESCRIPTION],
                ['serviceCode', self::SERVICE_CODE],
                ['shipperName', self::SHIPPER_NAME],
                ['shipperNumber', self::SHIPPER_NUMBER],
                ['shipperAddress', new AddressStub()],
                ['shipFromName', self::SHIP_FROM_NAME],
                ['shipFromAddress', new AddressStub()],
                ['shipToName', self::SHIP_TO_NAME],
                ['shipToAddress', new AddressStub()],
            ]
        );
    }

    public function testToJson()
    {
        $this->initPriceRequest();
        self::assertEquals(json_encode($this->resultArray, JSON_THROW_ON_ERROR), $this->model->toJson());
    }

    public function initPriceRequest()
    {
        $package = (new Package())
            ->setPackagingTypeCode(self::PACKAGING_TYPE_CODE)
            ->setWeightCode(self::WEIGHT_CODE)
            ->setWeight(self::WEIGHT);
        $address = new AddressStub();
        $this->model
            ->setSecurity(self::USERNAME, self::PASSWORD, self::ACCESS_LICENSE_NUMBER)
            ->setRequestOption(self::REQUEST_OPTION)
            ->setService(self::SERVICE_CODE, self::SERVICE_DESCRIPTION)
            ->setShipper(self::SHIPPER_NAME, self::SHIPPER_NUMBER, $address)
            ->setShipFrom(self::SHIP_FROM_NAME, $address)
            ->setShipTo(self::SHIP_TO_NAME, $address)
            ->addPackage($package);

        $addressArray = [
            'AddressLine'       => [
                $address->getStreet(),
                $address->getStreet2()
            ],
            'City'              => $address->getCity(),
            'StateProvinceCode' => $address->getRegionCode(),
            'PostalCode'        => $address->getPostalCode(),
            'CountryCode'       => $address->getCountryIso2(),
        ];
        $this->resultArray = [
            'UPSSecurity' => [
                'UsernameToken'      => [
                    'Username' => self::USERNAME,
                    'Password' => self::PASSWORD,
                ],
                'ServiceAccessToken' => [
                    'AccessLicenseNumber' => self::ACCESS_LICENSE_NUMBER,
                ],
            ],
            'RateRequest' => [
                'Request'  => [
                    'RequestOption' => self::REQUEST_OPTION,
                ],
                'Shipment' => [
                    'Shipper'  => [
                        'Name'          => self::SHIPPER_NAME,
                        'ShipperNumber' => self::SHIPPER_NUMBER,
                        'Address'       => $addressArray,
                    ],
                    'ShipTo'   => [
                        'Name'    => self::SHIP_TO_NAME,
                        'Address' => $addressArray,
                    ],
                    'ShipFrom' => [
                        'Name'    => self::SHIP_FROM_NAME,
                        'Address' => $addressArray,
                    ],
                    'Package'  => [$package->toArray()],
                    'Service'  => [
                        'Code'        => self::SERVICE_CODE,
                        'Description' => self::SERVICE_DESCRIPTION,
                    ],
                ],
            ],
        ];
    }
}
