<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Client\RateService\Request\Factory;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory\FedexRateServiceRequestFactory;
use Oro\Bundle\FedexShippingBundle\Client\Request\Factory\FedexRequestByContextAndSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use PHPUnit\Framework\TestCase;

class FedexRateServiceRequestFactoryTest extends TestCase
{
    const KEY = 'key';
    const PASS = 'pass';
    const ACCOUNT_NUMBER = 'account';
    const METER_NUMBER = 'meter';
    const PICKUP_TYPE = 'pickup';

    /**
     * @var SymmetricCrypterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $crypter;

    /**
     * @var FedexRequestByContextAndSettingsFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $lineItemsFactory;

    /**
     * @var FedexRateServiceRequestFactory
     */
    private $factory;

    protected function setUp()
    {
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);
        $this->lineItemsFactory = $this->createMock(FedexRequestByContextAndSettingsFactoryInterface::class);

        $this->factory = new FedexRateServiceRequestFactory(
            $this->crypter,
            $this->lineItemsFactory
        );
    }

    public function testCreate()
    {
        $packages = $this->createPackages();
        $settings = $this->createSettings();
        $context = $this->createContext();

        $this->crypter
            ->expects(static::once())
            ->method('decryptData')
            ->with(self::PASS)
            ->willReturn(self::PASS);

        $this->lineItemsFactory
            ->expects(static::once())
            ->method('create')
            ->with($settings, $context)
            ->willReturn($packages);

        static::assertEquals($this->getExpectedRequest(), $this->factory->create($settings, $context));
    }

    /**
     * @return FedexRequest
     */
    private function createPackages(): FedexRequest
    {
        return new FedexRequest(['1', '2']);
    }

    /**
     * @return ShippingContextInterface
     */
    private function createContext(): ShippingContextInterface
    {
        $context = new ShippingContext([
            ShippingContext::FIELD_SHIPPING_ORIGIN => $this->createShipperAddress(),
            ShippingContext::FIELD_SHIPPING_ADDRESS => $this->createRecipientAddress(),
        ]);

        return $context;
    }

    /**
     * @return Address
     */
    private function createShipperAddress()
    {
        $address = new Address();

        $address
            ->setStreet('street')
            ->setCity('city')
            ->setRegion(new Region('R'))
            ->setPostalCode('1234')
            ->setCountry(new Country('C'));

        return $address;
    }

    /**
     * @return Address
     */
    private function createRecipientAddress()
    {
        $address = new Address();

        $address
            ->setStreet('street2')
            ->setCity('city2')
            ->setRegion(new Region('R2'))
            ->setPostalCode('4321')
            ->setCountry(new Country('C2'));

        return $address;
    }

    /**
     * @return FedexIntegrationSettings
     */
    private function createSettings(): FedexIntegrationSettings
    {
        $settings = new FedexIntegrationSettings();

        $settings
            ->setKey(self::KEY)
            ->setPassword(self::PASS)
            ->setMeterNumber(self::METER_NUMBER)
            ->setAccountNumber(self::ACCOUNT_NUMBER)
            ->setPickupType(self::PICKUP_TYPE);

        return $settings;
    }

    /**
     * @param Address $address
     *
     * @return array
     */
    private function getExpectedAddress(Address $address): array
    {
        return [
            'StreetLines' => [
                $address->getStreet(),
                $address->getStreet2(),
            ],
            'City' => $address->getCity(),
            'StateOrProvinceCode' => $address->getRegionCode(),
            'PostalCode' => $address->getPostalCode(),
            'CountryCode' => $address->getCountryIso2(),
        ];
    }

    private function getExpectedRequest(): FedexRequest
    {
        $packages = $this->createPackages()->getRequestData();

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
                'Shipper' => [
                    'Address' => $this->getExpectedAddress($this->createShipperAddress())
                ],
                'Recipient' => [
                    'Address' => $this->getExpectedAddress($this->createRecipientAddress())
                ],
                'PackageCount' => count($packages),
                'RequestedPackageLineItems' => $packages,
            ],
        ]);
    }
}
