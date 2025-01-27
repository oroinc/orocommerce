<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\AddressValidation\Client\Request\Factory;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressValidationBundle\Client\Request\AddressValidationRequest;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\UPSBundle\AddressValidation\Client\Request\Factory\UPSAddressValidationRequestFactory;
use Oro\Bundle\UPSBundle\AddressValidation\Client\UPSAddressValidationClient;
use PHPUnit\Framework\TestCase;

final class UPSAddressValidationRequestFactoryTest extends TestCase
{
    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(AddressValidationRequest $expected, CustomerAddress $address): void
    {
        $factory = new UPSAddressValidationRequestFactory();

        $request = $factory->create($address);

        self::assertEquals($expected, $request);
    }

    public function createDataProvider(): array
    {
        $address = new CustomerAddress();
        $address->setStreet('Test Street');
        $address->setStreet2('Test Street2');
        $address->setCity('Test City');
        $address->setRegion((new Region('region_code'))->setCode('region_code'));
        $address->setPostalCode('1234');
        $address->setCountry(new Country('iso2Code'));

        $url = UPSAddressValidationClient::ADDRESS_VALIDATION_URI
            . UPSAddressValidationClient::REQUEST_OPTION_ADDRESS_VALIDATION;

        return [
            'empty address data' => [
                'expected' => new AddressValidationRequest($url, [
                    'XAVRequest' => [
                        'AddressKeyFormat' => [
                            'AddressLine' => [null, null],
                            'PoliticalDivision2' => null,
                            'PoliticalDivision1' => '',
                            'PostcodePrimaryLow' => null,
                            'CountryCode' => '',
                        ],
                    ],
                ]),
                'address' => new CustomerAddress(),
            ],
            'with address data' => [
                'expected' => new AddressValidationRequest($url, [
                    'XAVRequest' => [
                        'AddressKeyFormat' => [
                            'AddressLine' => ['Test Street', 'Test Street2'],
                            'PoliticalDivision2' => 'Test City',
                            'PoliticalDivision1' => 'region_code',
                            'PostcodePrimaryLow' => '1234',
                            'CountryCode' => 'iso2Code',
                        ],
                    ],
                ]),
                'address' => $address,
            ],
        ];
    }
}
