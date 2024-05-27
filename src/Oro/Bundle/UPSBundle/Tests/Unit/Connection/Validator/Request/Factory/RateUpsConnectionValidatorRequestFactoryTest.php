<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Connection\Validator\Request\Factory;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\UPSBundle\Client\Request\UpsClientRequest;
use Oro\Bundle\UPSBundle\Connection\Validator\Request\Factory\RateUpsConnectionValidatorRequestFactory;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;

class RateUpsConnectionValidatorRequestFactoryTest extends \PHPUnit\Framework\TestCase
{
    private const USERNAME = 'user';
    private const PASS = 'pass';
    private const KEY = 'key';
    private const COUNTRY_CODE = 'country';
    private const WEIGHT_UNIT = 'kg';

    public function testCreateByTransport()
    {
        $crypter = $this->createMock(SymmetricCrypterInterface::class);
        $crypter->expects(self::once())
            ->method('decryptData')
            ->willReturn(self::PASS);

        $transport = new UPSTransport();
        $transport->setUpsApiKey(self::KEY)
            ->setUpsApiUser(self::USERNAME)
            ->setUpsApiPassword(self::PASS)
            ->setUpsCountry(new Country(self::COUNTRY_CODE))
            ->setUpsUnitOfWeight(self::WEIGHT_UNIT);

        $expected = new UpsClientRequest([
            UpsClientRequest::FIELD_URL => 'Rate',
            UpsClientRequest::FIELD_REQUEST_DATA => $this->getRequestData(),
        ]);

        $factory = new RateUpsConnectionValidatorRequestFactory($crypter);

        self::assertEquals($expected, $factory->createByTransport($transport));
    }

    private function getRequestData(): array
    {
        return [
            'UPSSecurity' => [
                'UsernameToken' => [
                    'Username' => self::USERNAME,
                    'Password' => self::PASS,
                ],
                'ServiceAccessToken' => [
                    'AccessLicenseNumber' => self::KEY,
                ],
            ],
            'RateRequest' => [
                'Request' => [
                    'RequestOption' => 'Shop',
                ],
                'Shipment' => [
                    'Shipper' => [
                        'Name' => 'Company2',
                        'Address' => [
                            'PostalCode' => '10001',
                            'CountryCode' => self::COUNTRY_CODE,
                        ]
                    ],
                    'ShipTo' => [
                        'Name' => 'Company1',
                        'Address' =>[
                            'PostalCode' => '10001',
                            'CountryCode' => self::COUNTRY_CODE,
                        ]
                    ],
                    'ShipFrom' => [
                        'Name' => 'Company2',
                        'Address' =>[
                            'PostalCode' => '10001',
                            'CountryCode' => self::COUNTRY_CODE,
                        ]
                    ],
                    'Service' => [
                        'Code' => '03'
                    ],
                    'Package' => [
                        0 => [
                            'PackagingType' => [
                                'Code' => '02',
                            ],
                            'PackageWeight' => [
                                'UnitOfMeasurement' => [
                                    'Code' => self::WEIGHT_UNIT,
                                ],
                                'Weight' => '10',
                            ],
                        ]
                    ],
                ],
            ],
        ];
    }
}
