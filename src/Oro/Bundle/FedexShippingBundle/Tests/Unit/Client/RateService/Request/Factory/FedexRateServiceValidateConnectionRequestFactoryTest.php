<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Client\RateService\Request\Factory;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory\FedexRateServiceValidateConnectionRequestFactory;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;
use PHPUnit\Framework\TestCase;

class FedexRateServiceValidateConnectionRequestFactoryTest extends TestCase
{
    const KEY = 'key';
    const PASS = 'pass';
    const ACCOUNT_NUMBER = 'account';
    const METER_NUMBER = 'meter';
    const UNIT_OF_WEIGHT = 'unit';
    const PICKUP_TYPE = 'pickup';
    const STREET = 'street';
    const CITY = 'city';
    const REGION = 'region';
    const POSTAL_CODE = 'postal';
    const COUNTRY = 'country';

    /**
     * @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $crypter;

    /**
     * @var ShippingOriginProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shippingOriginProvider;

    /**
     * @var FedexRateServiceValidateConnectionRequestFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);
        $this->shippingOriginProvider = $this->createMock(ShippingOriginProvider::class);

        $this->factory = new FedexRateServiceValidateConnectionRequestFactory(
            $this->crypter,
            $this->shippingOriginProvider
        );
    }

    public function testCreate()
    {
        $settings = $this->createSettings();
        $shippingOrigin = $this->createShippingOrigin();

        $this->crypter
            ->expects(static::once())
            ->method('decryptData')
            ->willReturn(self::PASS);

        $this->shippingOriginProvider
            ->expects(static::once())
            ->method('getSystemShippingOrigin')
            ->willReturn($shippingOrigin);

        static::assertEquals(
            $this->getExpectedRequest(),
            $this->factory->create($settings)
        );
    }

    private function createShippingOrigin(): ShippingOrigin
    {
        return new ShippingOrigin([
            'street' => self::STREET,
            'city' => self::CITY,
            'region' => (new Region(self::REGION))->setCode(self::REGION),
            'postalCode' => self::POSTAL_CODE,
            'country' => new Country(self::COUNTRY),
        ]);
    }

    private function createSettings(): FedexIntegrationSettings
    {
        $settings = new FedexIntegrationSettings();

        $settings
            ->setKey(self::KEY)
            ->setPassword(self::PASS)
            ->setMeterNumber(self::METER_NUMBER)
            ->setAccountNumber(self::ACCOUNT_NUMBER)
            ->setPickupType(self::PICKUP_TYPE)
            ->setUnitOfWeight(self::UNIT_OF_WEIGHT);

        return $settings;
    }

    private function getExpectedAddress(): array
    {
        return [
            'Address' => [
                'StreetLines' => [
                    self::STREET
                ],
                'City' => self::CITY,
                'StateOrProvinceCode' => self::REGION,
                'PostalCode' => self::POSTAL_CODE,
                'CountryCode' => self::COUNTRY,
            ]
        ];
    }

    private function getExpectedRequest(): FedexRequest
    {
        return new FedexRequest([
            'WebAuthenticationDetail' => [
                'UserCredential' => [
                    'Key' => self::KEY,
                    'Password' => self::PASS,
                ]
            ],
            'ClientDetail' => [
                'AccountNumber' => self::ACCOUNT_NUMBER,
                'MeterNumber' => self::METER_NUMBER,
            ],
            'Version' => [
                'ServiceId' => 'crs',
                'Major' => '20',
                'Intermediate' => '0',
                'Minor' => '0'
            ],
            'RequestedShipment' => [
                'DropoffType' => self::PICKUP_TYPE,
                'Shipper' => $this->getExpectedAddress(),
                'Recipient' => $this->getExpectedAddress(),
                'PackageCount' => 1,
                'RequestedPackageLineItems' => [
                    'GroupPackageCount' => 1,
                    'Weight' => [
                        'Value' => '10',
                        'Units' => self::UNIT_OF_WEIGHT,
                    ],
                    'Dimensions' => [
                        'Length' => '5',
                        'Width' => '10',
                        'Height' => '10',
                        'Units' => FedexIntegrationSettings::DIMENSION_CM,
                    ],
                ],
            ],
        ]);
    }
}
