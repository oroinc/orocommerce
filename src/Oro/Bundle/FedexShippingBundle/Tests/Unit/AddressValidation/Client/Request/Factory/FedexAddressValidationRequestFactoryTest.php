<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\AddressValidation\Client\Request\Factory;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\AddressValidationBundle\Client\Request\AddressValidationRequest;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\FedexShippingBundle\AddressValidation\Client\FedexAddressValidationClient;
use Oro\Bundle\FedexShippingBundle\AddressValidation\Client\Request\Factory\FedexAddressValidationRequestFactory;
use PHPUnit\Framework\TestCase;

final class FedexAddressValidationRequestFactoryTest extends TestCase
{
    /**
     * @dataProvider createDataProvider
     */
    public function testCreate(AddressValidationRequest $expected, CustomerAddress $address): void
    {
        $factory = new FedexAddressValidationRequestFactory();

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

        return [
            'empty address data' => [
                'expected' => new AddressValidationRequest(FedexAddressValidationClient::ADDRESS_VALIDATION_URI, [
                    'addressesToValidate' => [
                        [
                            'address' => [
                                'streetLines' => [null, null],
                                'city' => null,
                                'stateOrProvinceCode' => '',
                                'postalCode' => null,
                                'countryCode' => '',
                            ],
                        ],
                    ],
                ]),
                'address' => new CustomerAddress(),
            ],
            'with address data' => [
                'expected' => new AddressValidationRequest(FedexAddressValidationClient::ADDRESS_VALIDATION_URI, [
                    'addressesToValidate' => [
                        [
                            'address' => [
                                'streetLines' => ['Test Street', 'Test Street2'],
                                'city' => 'Test City',
                                'stateOrProvinceCode' => 'region_code',
                                'postalCode' => '1234',
                                'countryCode' => 'iso2Code',
                            ],
                        ],
                    ],
                ]),
                'address' => $address,
            ]
        ];
    }
}
