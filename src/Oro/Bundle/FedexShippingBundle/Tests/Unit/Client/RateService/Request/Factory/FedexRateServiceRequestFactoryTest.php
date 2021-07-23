<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Client\RateService\Request\Factory;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory\FedexRateServiceRequestFactory;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\FedexRateServiceRequestSettings;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingServiceRule;
use Oro\Bundle\FedexShippingBundle\Factory\FedexPackagesByLineItemsAndPackageSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Factory\FedexPackageSettingsByIntegrationSettingsAndRuleFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Model\FedexPackageSettingsInterface;
use Oro\Bundle\FedexShippingBundle\Modifier\ShippingLineItemCollectionBySettingsModifierInterface;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\ShippingLineItemCollectionInterface;
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
     * @var SymmetricCrypterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $crypter;

    /**
     * @var FedexPackageSettingsByIntegrationSettingsAndRuleFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $packageSettingsFactory;

    /**
     * @var FedexPackagesByLineItemsAndPackageSettingsFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $packagesFactory;

    /**
     * @var ShippingLineItemCollectionBySettingsModifierInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $convertToFedexUnitsModifier;

    /**
     * @var FedexRateServiceRequestFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->crypter = $this->createMock(SymmetricCrypterInterface::class);
        $this->packageSettingsFactory = $this->createMock(
            FedexPackageSettingsByIntegrationSettingsAndRuleFactoryInterface::class
        );
        $this->packagesFactory = $this->createMock(FedexPackagesByLineItemsAndPackageSettingsFactoryInterface::class);
        $this->convertToFedexUnitsModifier = $this->createMock(
            ShippingLineItemCollectionBySettingsModifierInterface::class
        );

        $this->factory = new FedexRateServiceRequestFactory(
            $this->crypter,
            $this->packageSettingsFactory,
            $this->packagesFactory,
            $this->convertToFedexUnitsModifier
        );
    }

    public function testCreateNoPackages()
    {
        $context = $this->createContext();
        $integrationSettings = new FedexIntegrationSettings();

        $settings = new FedexRateServiceRequestSettings(
            $integrationSettings,
            $context,
            new ShippingServiceRule()
        );

        $packageSettings = $this->createMock(FedexPackageSettingsInterface::class);
        $this->packageSettingsFactory
            ->expects(static::once())
            ->method('create')
            ->with($integrationSettings)
            ->willReturn($packageSettings);

        $lineItemsWithOptions = new DoctrineShippingLineItemCollection([]);

        $lineItemsConverted = new DoctrineShippingLineItemCollection([]);
        $this->convertToFedexUnitsModifier
            ->expects(static::once())
            ->method('modify')
            ->with($lineItemsWithOptions, $integrationSettings)
            ->willReturn($lineItemsConverted);

        $this->packagesFactory
            ->expects(static::once())
            ->method('create')
            ->with($lineItemsConverted, $packageSettings)
            ->willReturn([]);

        static::assertNull($this->factory->create($settings));
    }

    /**
     * @dataProvider brokenContextDataProvider
     */
    public function testCreateNotAllDataFilled(ShippingContext $context)
    {
        $packages = $this->createPackages();
        $integrationSettings = $this->createIntegrationSettings();

        $rule = new ShippingServiceRule();
        $rule
            ->setServiceType('service')
            ->setResidentialAddress(true);

        $settings = new FedexRateServiceRequestSettings(
            $integrationSettings,
            $context,
            $rule
        );

        $this->packageSettingsFactory
            ->expects(static::once())
            ->method('create')
            ->with($integrationSettings)
            ->willReturn($this->createMock(FedexPackageSettingsInterface::class));

        $this->convertToFedexUnitsModifier
            ->expects(static::once())
            ->method('modify')
            ->willReturn($this->createMock(ShippingLineItemCollectionInterface::class));

        $this->packagesFactory
            ->expects(static::once())
            ->method('create')
            ->willReturn($packages);

        $this->crypter
            ->expects(static::never())
            ->method('decryptData');

        static::assertNull($this->factory->create($settings));
    }

    /**
     * @retrun array
     */
    public function brokenContextDataProvider(): array
    {
        return [
            'empty shipping origin' => [
                new ShippingContext([
                    ShippingContext::FIELD_SHIPPING_ORIGIN => null,
                    ShippingContext::FIELD_SHIPPING_ADDRESS => $this->createRecipientAddress(),
                    ShippingContext::FIELD_LINE_ITEMS => new DoctrineShippingLineItemCollection([]),
                ])
            ],
            'empty shipping address' => [
                new ShippingContext([
                    ShippingContext::FIELD_SHIPPING_ORIGIN => $this->createShipperAddress(),
                    ShippingContext::FIELD_SHIPPING_ADDRESS => null,
                    ShippingContext::FIELD_LINE_ITEMS => new DoctrineShippingLineItemCollection([]),
                ])
            ]
        ];
    }

    public function testCreate()
    {
        $packages = $this->createPackages();
        $integrationSettings = $this->createIntegrationSettings();
        $context = $this->createContext();

        $rule = new ShippingServiceRule();
        $rule
            ->setServiceType('service')
            ->setResidentialAddress(true);

        $settings = new FedexRateServiceRequestSettings(
            $integrationSettings,
            $context,
            $rule
        );

        $this->packageSettingsFactory
            ->expects(static::once())
            ->method('create')
            ->with($integrationSettings)
            ->willReturn($this->createMock(FedexPackageSettingsInterface::class));

        $this->convertToFedexUnitsModifier
            ->expects(static::once())
            ->method('modify')
            ->willReturn($this->createMock(ShippingLineItemCollectionInterface::class));

        $this->packagesFactory
            ->expects(static::once())
            ->method('create')
            ->willReturn($packages);

        $this->crypter
            ->expects(static::once())
            ->method('decryptData')
            ->with(self::PASS)
            ->willReturn(self::PASS);

        static::assertEquals($this->getExpectedRequest(), $this->factory->create($settings));
    }

    private function createPackages(): array
    {
        return ['1', '2'];
    }

    private function createContext(): ShippingContextInterface
    {
        $context = new ShippingContext([
            ShippingContext::FIELD_SHIPPING_ORIGIN => $this->createShipperAddress(),
            ShippingContext::FIELD_SHIPPING_ADDRESS => $this->createRecipientAddress(),
            ShippingContext::FIELD_LINE_ITEMS => new DoctrineShippingLineItemCollection([]),
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

    private function createIntegrationSettings(): FedexIntegrationSettings
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
        $packages = $this->createPackages();
        $recipientAddress = $this->createRecipientAddress();

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
                'ServiceType' => 'service',
                'DropoffType' => self::PICKUP_TYPE,
                'Shipper' => [
                    'Address' => $this->getExpectedAddress($this->createShipperAddress())
                ],
                'Recipient' => [
                    'Address' => [
                        'StreetLines' => [
                            $recipientAddress->getStreet(),
                            $recipientAddress->getStreet2(),
                        ],
                        'City' => $recipientAddress->getCity(),
                        'StateOrProvinceCode' => $recipientAddress->getRegionCode(),
                        'PostalCode' => $recipientAddress->getPostalCode(),
                        'CountryCode' => $recipientAddress->getCountryIso2(),
                        'Residential' => true,
                    ]
                ],
                'PackageCount' => count($packages),
                'RequestedPackageLineItems' => $packages,
            ],
        ]);
    }
}
