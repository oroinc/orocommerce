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

class TaxationAddressProviderTest extends \PHPUnit_Framework_TestCase
{
    const EU = 'UK';
    const US = 'US';

    const DIGITAL_TAX_CODE = 'DIGITAL_TAX_CODE';

    /**
     * @var TaxationSettingsProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $settingsProvider;

    /**
     * @var TaxationAddressProvider
     */
    protected $addressProvider;

    protected function setUp()
    {
        $this->settingsProvider = $this
            ->getMockBuilder('\Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->addressProvider = new TaxationAddressProvider($this->settingsProvider);
    }

    protected function tearDown()
    {
        unset($this->settingsProvider, $this->addressProvider);
    }

    public function testGetOriginAddress()
    {
        $address = new Address();

        $this->settingsProvider
            ->expects($this->once())
            ->method('getOrigin')
            ->willReturn($address);

        $this->assertSame($address, $this->addressProvider->getOriginAddress());
    }

    /**
     * @dataProvider getTaxationAddressProvider
     *
     * @param AbstractAddress|null $expectedResult
     * @param string $destination
     * @param Address $origin
     * @param bool $originByDefault
     * @param OrderAddress $billingAddress
     * @param OrderAddress $shippingAddress
     * @param array $exclusions
     */
    public function testGetTaxationAddress(
        $expectedResult,
        $destination,
        $origin,
        $originByDefault,
        $billingAddress,
        $shippingAddress,
        $exclusions
    ) {
        $this->settingsProvider->expects($this->any())->method('getOrigin')->willReturn($origin);

        $this->settingsProvider
            ->expects($exclusions !== null ? $this->once() : $this->never())
            ->method('getBaseAddressExclusions')
            ->willReturn($exclusions);

        $this->settingsProvider
            ->expects($this->once())
            ->method('getDestination')
            ->willReturn($destination);

        $this->settingsProvider
            ->expects($this->once())
            ->method('isOriginBaseByDefaultAddressType')
            ->willReturn($originByDefault);

        $this->assertEquals(
            $expectedResult,
            $this->addressProvider->getTaxationAddress($billingAddress, $shippingAddress)
        );
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getTaxationAddressProvider()
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
                    'option' => TaxationSettingsProvider::USE_AS_BASE_SHIPPING_ORIGIN,
                ]
            ),
        ];

        $usRegionTextAddress = (new OrderAddress())->setCountry($countryUS)->setRegionText('US LA');
        $usAlRegionAddress = (new OrderAddress())->setCountry($countryUS)->setRegion(new Region('AL'));

        return [
            'billing address' => [
                $billingAddress,
                TaxationSettingsProvider::DESTINATION_BILLING_ADDRESS,
                null,
                false,
                $billingAddress,
                $shippingAddress,
                []
            ],
            'shipping address' =>[
                $shippingAddress,
                TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS,
                null,
                false,
                $billingAddress,
                $shippingAddress,
                []
            ],
            'null address' =>[
                null,
                null,
                null,
                null,
                $billingAddress,
                $shippingAddress,
                null
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
                null,
                null,
                $billingAddress,
                $shippingAddress,
                $exclusions
            ],
            'shipping address with exclusion (use origin as base)' => [
                $originAddress,
                TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS,
                $originAddress,
                null,
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
                null,
            ],
            'shipping address with exclusion (use origin as base) region text do not match' => [
                $usRegionTextAddress,
                TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS,
                $originAddress,
                null,
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
                            'option' => TaxationSettingsProvider::USE_AS_BASE_SHIPPING_ORIGIN,
                        ]
                    ),
                ]
            ],
            'shipping address with exclusion (use origin as base) region do not match' => [
                $usAlRegionAddress,
                TaxationSettingsProvider::DESTINATION_SHIPPING_ADDRESS,
                $originAddress,
                null,
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
                            'option' => TaxationSettingsProvider::USE_AS_BASE_SHIPPING_ORIGIN,
                        ]
                    ),
                ]
            ],
        ];
    }

    /**
     * @dataProvider isDigitalProductTaxCodeProvider
     * @param string $country
     * @param string $taxCode
     * @param bool $expected
     */
    public function testIsDigitalProductTaxCode($country, $taxCode, $expected)
    {
        $this->settingsProvider
            ->expects($country === self::EU ? $this->once() : $this->never())
            ->method('getDigitalProductsTaxCodesEU')
            ->willReturn([self::DIGITAL_TAX_CODE]);

        $this->settingsProvider
            ->expects($country === self::US ? $this->once() : $this->never())
            ->method('getDigitalProductsTaxCodesUS')
            ->willReturn([self::DIGITAL_TAX_CODE]);

        $this->assertEquals($expected, $this->addressProvider->isDigitalProductTaxCode($country, $taxCode));
    }

    /**
     * @return array
     */
    public function isDigitalProductTaxCodeProvider()
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
