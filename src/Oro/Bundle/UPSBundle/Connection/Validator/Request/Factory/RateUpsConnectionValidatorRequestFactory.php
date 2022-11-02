<?php

namespace Oro\Bundle\UPSBundle\Connection\Validator\Request\Factory;

use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\UPSBundle\Client\Request\UpsClientRequest;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;

class RateUpsConnectionValidatorRequestFactory implements UpsConnectionValidatorRequestFactoryInterface
{
    /**
     * @internal
     */
    const REQUEST_URL = 'Rate';

    /**
     * @var SymmetricCrypterInterface
     */
    private $crypter;

    public function __construct(SymmetricCrypterInterface $crypter)
    {
        $this->crypter = $crypter;
    }

    /**
     * {@inheritDoc}
     */
    public function createByTransport(UPSTransport $transport)
    {
        return new UpsClientRequest([
            UpsClientRequest::FIELD_URL => self::REQUEST_URL,
            UpsClientRequest::FIELD_REQUEST_DATA => $this->getRequestData($transport),
        ]);
    }

    /**
     * @param UPSTransport $transport
     *
     * @return array
     */
    private function getRequestData(UPSTransport $transport)
    {
        return [
            'UPSSecurity' => [
                'UsernameToken' => [
                    'Username' => $transport->getUpsApiUser(),
                    'Password' => $this->crypter->decryptData($transport->getUpsApiPassword()),
                ],
                'ServiceAccessToken' => [
                    'AccessLicenseNumber' => $transport->getUpsApiKey(),
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
                            'PostalCode' => '0000000000000000',
                            'CountryCode' => $transport->getUpsCountry()->getIso2Code(),
                        ]
                    ],
                    'ShipTo' => [
                        'Name' => 'Company1',
                        'Address' =>[
                            'PostalCode' => '0000000000000000',
                            'CountryCode' => $transport->getUpsCountry()->getIso2Code(),
                        ]
                    ],
                    'ShipFrom' => [
                        'Name' => 'Company2',
                        'Address' =>[
                            'PostalCode' => '0000000000000000',
                            'CountryCode' => $transport->getUpsCountry()->getIso2Code(),
                        ]
                    ],
                    'Package' => [
                        0 => [
                            'PackagingType' => [
                                'Code' => '02',
                            ],
                            'PackageWeight' => [
                                'UnitOfMeasurement' => [
                                    'Code' => $transport->getUpsUnitOfWeight(),
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
