<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Client\RateService\Request\Factory;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory\FedexRateServiceValidateConnectionRequestFactory;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\ShippingBundle\Model\ShippingOrigin;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;
use PHPUnit\Framework\TestCase;

class FedexRateServiceValidateConnectionRequestFactoryTest extends TestCase
{
    private const KEY = 'key';
    private const PASS = 'pass';
    private const ACCOUNT_NUMBER = 'account';
    private const UNIT_OF_WEIGHT = 'unit';
    private const PICKUP_TYPE = 'pickup';
    private const STREET = 'street';
    private const CITY = 'city';
    private const REGION = 'region';
    private const POSTAL_CODE = 'postal';
    private const COUNTRY = 'country';

    /** @var ShippingOriginProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingOriginProvider;

    /** @var FedexRateServiceValidateConnectionRequestFactory */
    private $factory;

    protected function setUp(): void
    {
        $this->shippingOriginProvider = $this->createMock(ShippingOriginProvider::class);

        $this->factory = new FedexRateServiceValidateConnectionRequestFactory(
            $this->shippingOriginProvider
        );
    }

    public function testCreate()
    {
        $settings = $this->createSettings();
        $shippingOrigin = $this->createShippingOrigin();

        $this->shippingOriginProvider->expects(self::once())
            ->method('getSystemShippingOrigin')
            ->willReturn($shippingOrigin);

        self::assertEquals(
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
            ->setClientId(self::KEY)
            ->setClientSecret(self::PASS)
            ->setAccountNumber(self::ACCOUNT_NUMBER)
            ->setPickupType(self::PICKUP_TYPE)
            ->setUnitOfWeight(self::UNIT_OF_WEIGHT);

        return $settings;
    }

    private function getExpectedAddress(): array
    {
        return [
            'address' => [
                'streetLines' => [
                    self::STREET
                ],
                'city' => self::CITY,
                'stateOrProvinceCode' => self::REGION,
                'postalCode' => self::POSTAL_CODE,
                'countryCode' => self::COUNTRY,
            ]
        ];
    }

    private function getExpectedRequest(): FedexRequest
    {
        return new FedexRequest(
            '/rate/v1/rates/quotes',
            [
                'accountNumber' => ['value' => self::ACCOUNT_NUMBER],
                'requestedShipment' => [
                    "rateRequestType" => ["ACCOUNT"],
                    'pickupType' => self::PICKUP_TYPE,
                    'shipper' => $this->getExpectedAddress(),
                    'recipient' => $this->getExpectedAddress(),
                    'totalPackageCount' => 1,
                    'requestedPackageLineItems' => [[
                        'groupPackageCount' => 1,
                        'weight' => [
                            'value' => 10,
                            'units' => self::UNIT_OF_WEIGHT,
                        ],
                        'dimensions' => [
                            'length' => 5,
                            'width' => 10,
                            'height' => 10,
                            'units' => FedexIntegrationSettings::DIMENSION_CM,
                        ],
                    ]],
                ],
            ],
            true
        );
    }
}
