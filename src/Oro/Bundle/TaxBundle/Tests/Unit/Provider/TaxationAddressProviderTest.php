<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Provider;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\TaxBundle\Model\Address;
use Oro\Bundle\TaxBundle\Model\TaxBaseExclusion;
use Oro\Bundle\TaxBundle\Provider\TaxationAddressProvider;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class TaxationAddressProviderTest extends \PHPUnit\Framework\TestCase
{
    private const EU = 'UK';
    private const US = 'US';

    private const DIGITAL_TAX_CODE = 'DIGITAL_TAX_CODE';

    /** @var TaxationSettingsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $settingsProvider;

    /** @var TaxationAddressProvider */
    private $addressProvider;

    protected function setUp(): void
    {
        $this->settingsProvider = $this->createMock(TaxationSettingsProvider::class);

        $this->addressProvider = new TaxationAddressProvider($this->settingsProvider);
    }

    public function testGetOriginAddress()
    {
        $address = new Address();

        $this->settingsProvider->expects($this->once())
            ->method('getOrigin')
            ->willReturn($address);

        $this->assertSame($address, $this->addressProvider->getOriginAddress());
    }

    /**
     * @dataProvider getTaxationAddressProvider
     */
    public function testGetTaxationAddress(
        ?AbstractAddress $expectedResult,
        ?string $destination,
        Address $origin,
        bool $originByDefault,
        ?OrderAddress $billingAddress,
        ?OrderAddress $shippingAddress,
        array $exclusions
    ) {
        $this->settingsProvider->expects($this->any())
            ->method('getOrigin')
            ->willReturn($origin);

        $this->settingsProvider
            ->expects($destination && ($shippingAddress || $billingAddress) ? $this->once() : $this->never())
            ->method('getBaseAddressExclusions')
            ->willReturn($exclusions);

        $this->settingsProvider->expects($this->once())
            ->method('getDestination')
            ->willReturn($destination);

        $this->settingsProvider->expects($this->once())
            ->method('isOriginBaseByDefaultAddressType')
            ->willReturn($originByDefault);

        $this->assertEquals(
            $expectedResult,
            $this->addressProvider->getTaxationAddress($billingAddress, $shippingAddress)
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getTaxationAddressProvider(): array
    {
        $countryUS = new Country('US');
        $countryCA = new Country('CA');

        $regionUSLA = new Region('US-LA');

        $originAddress = new Address();

        $billingAddress = new OrderAddress();
        $billingAddress->setCountry($countryUS);
        $billingAddress->setRegion($regionUSLA);

        $shippingAddress = new OrderAddress();
        $shippingAddress->setCountry($countryCA);

        $exclusions = [
            new TaxBaseExclusion(
                [
                    'country' => $countryUS,
                    'region' => $regionUSLA,
                    'option' => TaxationSettingsProvider::USE_AS_BASE_DESTINATION,
                ]
            ),
            new TaxBaseExclusion(
                [
                    'country' => $countryCA,
                    'region' => null,
                    'option' => TaxationSettingsProvider::USE_AS_BASE_ORIGIN,
                ]
            ),
        ];

        $usRegionTextAddress = (new OrderAddress())->setCountry($countryUS)->setRegionText('US LA');
        $usAlRegionAddress = (new OrderAddress())->setCountry($countryUS)->setRegion(new Region('AL'));

        return [
            'billing address' => [
                $billingAddress,
                TaxationSettingsProvider::DESTINATION_BILLING_ADDRESS,
                new Address(),
                false,
                $billingAddress,
                $shippingAddress,
                []
            ],
            'shipping address' =>[
                $shippingAddress,
                TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS,
                new Address(),
                false,
                $billingAddress,
                $shippingAddress,
                []
            ],
            'null address' =>[
                null,
                null,
                new Address(),
                false,
                $billingAddress,
                $shippingAddress,
                []
            ],
            'origin address by default' => [
                $originAddress,
                TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS,
                $originAddress,
                true,
                $billingAddress,
                $shippingAddress,
                []
            ],
            'billing address with exclusion (use destination as base)' => [
                $billingAddress,
                TaxationSettingsProvider::DESTINATION_BILLING_ADDRESS,
                new Address(),
                false,
                $billingAddress,
                $shippingAddress,
                $exclusions
            ],
            'shipping address with exclusion (use origin as base)' => [
                $originAddress,
                TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS,
                $originAddress,
                false,
                $billingAddress,
                $shippingAddress,
                $exclusions
            ],
            'shipping by default return origin if no billing and shipping' => [
                $originAddress,
                TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS,
                $originAddress,
                true,
                null,
                null,
                [],
            ],
            'shipping address with exclusion (use origin as base) region text do not match' => [
                $usRegionTextAddress,
                TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS,
                $originAddress,
                false,
                $billingAddress,
                $usRegionTextAddress,
                [
                    new TaxBaseExclusion(
                        [
                            'country' => $countryUS,
                            'region' => $regionUSLA,
                            'option' => TaxationSettingsProvider::USE_AS_BASE_DESTINATION,
                        ]
                    ),
                    new TaxBaseExclusion(
                        [
                            'country' => $countryCA,
                            'region' => null,
                            'option' => TaxationSettingsProvider::USE_AS_BASE_ORIGIN,
                        ]
                    ),
                ]
            ],
            'shipping address with exclusion (use origin as base) region do not match' => [
                $usAlRegionAddress,
                TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS,
                $originAddress,
                false,
                $billingAddress,
                $usAlRegionAddress,
                [
                    new TaxBaseExclusion(
                        [
                            'country' => $countryUS,
                            'region' => $regionUSLA,
                            'option' => TaxationSettingsProvider::USE_AS_BASE_DESTINATION,
                        ]
                    ),
                    new TaxBaseExclusion(
                        [
                            'country' => $countryCA,
                            'region' => null,
                            'option' => TaxationSettingsProvider::USE_AS_BASE_ORIGIN,
                        ]
                    ),
                ]
            ],
        ];
    }

    /**
     * @dataProvider isDigitalProductTaxCodeProvider
     */
    public function testIsDigitalProductTaxCode(string $country, string $taxCode, bool $expected)
    {
        $this->settingsProvider->expects($country === self::EU ? $this->once() : $this->never())
            ->method('getDigitalProductsTaxCodesEU')
            ->willReturn([self::DIGITAL_TAX_CODE]);

        $this->settingsProvider->expects($country === self::US ? $this->once() : $this->never())
            ->method('getDigitalProductsTaxCodesUS')
            ->willReturn([self::DIGITAL_TAX_CODE]);

        $this->assertEquals($expected, $this->addressProvider->isDigitalProductTaxCode($country, $taxCode));
    }

    public function isDigitalProductTaxCodeProvider(): array
    {
        return [
            'EU not digital' => [
                'country' => self::EU,
                'taxCode' => 'TAX_CODE',
                'expected' => false,
            ],
            'EU digital' => [
                'country' => self::EU,
                'taxCode' => self::DIGITAL_TAX_CODE,
                'expected' => true,
            ],
            'US not digital' => [
                'country' => self::US,
                'taxCode' => 'TAX_CODE',
                'expected' => false,
            ],
            'US digital' => [
                'country' => self::US,
                'taxCode' => self::DIGITAL_TAX_CODE,
                'expected' => true,
            ],
            'ANOTHER_COUNTRY not digital' => [
                'country' => 'ANOTHER_COUNTRY',
                'taxCode' => self::DIGITAL_TAX_CODE,
                'expected' => false,
            ]
        ];
    }
}
