<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Client\RateService\Request\Factory;

use Doctrine\Common\Collections\ArrayCollection;
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
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FedexRateServiceRequestFactoryTest extends TestCase
{
    private const KEY = 'key';
    private const PASS = 'pass';
    private const ACCOUNT_NUMBER = 'account';
    private const PICKUP_TYPE = 'pickup';

    private FedexPackageSettingsByIntegrationSettingsAndRuleFactoryInterface|MockObject $packageSettingsFactory;
    private FedexPackagesByLineItemsAndPackageSettingsFactoryInterface|MockObject $packagesFactory;
    private ShippingLineItemCollectionBySettingsModifierInterface|MockObject $convertToFedexUnitsModifier;
    private FedexRateServiceRequestFactory $factory;

    protected function setUp(): void
    {
        $this->packageSettingsFactory = $this->createMock(
            FedexPackageSettingsByIntegrationSettingsAndRuleFactoryInterface::class
        );
        $this->packagesFactory = $this->createMock(FedexPackagesByLineItemsAndPackageSettingsFactoryInterface::class);
        $this->convertToFedexUnitsModifier = $this->createMock(
            ShippingLineItemCollectionBySettingsModifierInterface::class
        );

        $this->factory = new FedexRateServiceRequestFactory(
            $this->packageSettingsFactory,
            $this->packagesFactory,
            $this->convertToFedexUnitsModifier
        );
    }

    public function testCreateNoPackages(): void
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
            ->expects(self::once())
            ->method('create')
            ->with($integrationSettings)
            ->willReturn($packageSettings);

        $lineItemsWithOptions = new ArrayCollection([]);

        $lineItemsConverted = new ArrayCollection([]);
        $this->convertToFedexUnitsModifier
            ->expects(self::once())
            ->method('modify')
            ->with($lineItemsWithOptions, $integrationSettings)
            ->willReturn($lineItemsConverted);

        $this->packagesFactory
            ->expects(self::once())
            ->method('create')
            ->with($lineItemsConverted, $packageSettings)
            ->willReturn([]);

        self::assertNull($this->factory->create($settings));
    }

    /**
     * @dataProvider brokenContextDataProvider
     */
    public function testCreateNotAllDataFilled(ShippingContext $context): void
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
            ->expects(self::once())
            ->method('create')
            ->with($integrationSettings)
            ->willReturn($this->createMock(FedexPackageSettingsInterface::class));

        $this->convertToFedexUnitsModifier
            ->expects(self::once())
            ->method('modify')
            ->willReturn(new ArrayCollection([]));

        $this->packagesFactory
            ->expects(self::once())
            ->method('create')
            ->willReturn($packages);

        self::assertNull($this->factory->create($settings));
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
                    ShippingContext::FIELD_LINE_ITEMS => new ArrayCollection([]),
                ])
            ],
            'empty shipping address' => [
                new ShippingContext([
                    ShippingContext::FIELD_SHIPPING_ORIGIN => $this->createShipperAddress(),
                    ShippingContext::FIELD_SHIPPING_ADDRESS => null,
                    ShippingContext::FIELD_LINE_ITEMS => new ArrayCollection([]),
                ])
            ]
        ];
    }

    public function testCreate(): void
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
            ->expects(self::once())
            ->method('create')
            ->with($integrationSettings)
            ->willReturn($this->createMock(FedexPackageSettingsInterface::class));

        $this->convertToFedexUnitsModifier
            ->expects(self::once())
            ->method('modify')
            ->willReturn(new ArrayCollection([]));

        $this->packagesFactory
            ->expects(self::once())
            ->method('create')
            ->willReturn($packages);

        self::assertEquals($this->getExpectedRequest(), $this->factory->create($settings));
    }

    private function createPackages(): array
    {
        return ['1', '2'];
    }

    private function createContext(): ShippingContextInterface
    {
        return new ShippingContext([
            ShippingContext::FIELD_SHIPPING_ORIGIN => $this->createShipperAddress(),
            ShippingContext::FIELD_SHIPPING_ADDRESS => $this->createRecipientAddress(),
            ShippingContext::FIELD_LINE_ITEMS => new ArrayCollection([]),
        ]);
    }

    private function createShipperAddress(): Address
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

    private function createRecipientAddress(): Address
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
            ->setClientId(self::KEY)
            ->setClientSecret(self::PASS)
            ->setAccountNumber(self::ACCOUNT_NUMBER)
            ->setPickupType(self::PICKUP_TYPE);

        return $settings;
    }

    private function getExpectedAddress(Address $address): array
    {
        return [
            'streetLines' => [
                $address->getStreet(),
                $address->getStreet2(),
            ],
            'city' => $address->getCity(),
            'stateOrProvinceCode' => $address->getRegionCode(),
            'postalCode' => $address->getPostalCode(),
            'countryCode' => $address->getCountryIso2(),
        ];
    }

    private function getExpectedRequest(): FedexRequest
    {
        $packages = $this->createPackages();
        $recipientAddress = $this->createRecipientAddress();

        return new FedexRequest(
            '/rate/v1/rates/quotes',
            [
                'accountNumber' => ['value' => self::ACCOUNT_NUMBER],
                'requestedShipment' => [
                    "rateRequestType" => ["ACCOUNT"],
                    'pickupType' => self::PICKUP_TYPE,
                    'shipper' => [
                        'address' => $this->getExpectedAddress($this->createShipperAddress())
                    ],
                    'recipient' => [
                        'address' => [
                            'streetLines' => [
                                $recipientAddress->getStreet(),
                                $recipientAddress->getStreet2(),
                            ],
                            'city' => $recipientAddress->getCity(),
                            'stateOrProvinceCode' => $recipientAddress->getRegionCode(),
                            'postalCode' => $recipientAddress->getPostalCode(),
                            'countryCode' => $recipientAddress->getCountryIso2(),
                            'residential' => true,
                        ]
                    ],
                    'totalPackageCount' => count($packages),
                    'requestedPackageLineItems' => $packages,
                    'serviceType' => 'service'
                ],
            ]
        );
    }
}
