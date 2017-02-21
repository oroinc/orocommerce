<?php

namespace Oro\Bundle\UPSBundle\Factory\Request;

use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Request\UpsClientRequest;

class RateUpsConnectionValidatorRequestFactory implements UpsConnectionValidatorRequestFactoryInterface
{
    /** @internal */
    const REQUEST_URL = 'Rate';

    /**
     * @var SymmetricCrypterInterface
     */
    private $crypter;

    /**
     * @param SymmetricCrypterInterface $crypter
     */
    public function __construct(SymmetricCrypterInterface $crypter)
    {
        $this->crypter = $crypter;
    }

    /**
     * {@inheritDoc}
     */
    public function createByTransport(UPSTransport $transport)
    {
        return new UpsClientRequest(self::REQUEST_URL, [
            'UPSSecurity' => [
                'UsernameToken' => [
                    'Username' => $transport->getApiUser(),
                    'Password' => $this->crypter->decryptData($transport->getApiPassword()),
                ],
                'ServiceAccessToken' => [
                    'AccessLicenseNumber' => $transport->getApiKey(),
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
                            'CountryCode' => $transport->getCountry()->getIso2Code(),
                        ]
                    ],
                    'ShipTo' => [
                        'Name' => 'Company1',
                        'Address' =>[
                            'PostalCode' => '0000000000000000',
                            'CountryCode' => $transport->getCountry()->getIso2Code(),
                        ]
                    ],
                    'ShipFrom' => [
                        'Name' => 'Company2',
                        'Address' =>[
                            'PostalCode' => '0000000000000000',
                            'CountryCode' => $transport->getCountry()->getIso2Code(),
                        ]
                    ],
                    'Package' => [
                        0 => [
                            'PackagingType' => [
                                'Code' => '02',
                            ],
                            'PackageWeight' => [
                                'UnitOfMeasurement' => [
                                    'Code' => $transport->getUnitOfWeight(),
                                ],
                                'Weight' => '10',
                            ],
                        ]
                    ],
                ],
            ],
        ]);
    }
}
