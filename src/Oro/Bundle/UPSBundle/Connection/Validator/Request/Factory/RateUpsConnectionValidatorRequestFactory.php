<?php

namespace Oro\Bundle\UPSBundle\Connection\Validator\Request\Factory;

use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\UPSBundle\Client\Request\UpsClientRequest;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;

/**
 * Factory that creates client request to validate UPS Shipping Rates
 */
class RateUpsConnectionValidatorRequestFactory implements UpsConnectionValidatorRequestFactoryInterface
{
    private const REQUEST_URL = 'Rate';

    /**
     * @internal
     * https://developer.ups.com/api/reference?loc=en_US#operation/Rate
     */
    private const REQUEST_URL_OAUTH = '/api/rating/v2403/Rate';

    public function __construct(
        private SymmetricCrypterInterface $crypter
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function createByTransport(UPSTransport $transport)
    {
        return new UpsClientRequest([
            UpsClientRequest::FIELD_URL => $this->isOAuthConfigured($transport)
                ? self::REQUEST_URL_OAUTH
                : self::REQUEST_URL,
            UpsClientRequest::FIELD_REQUEST_DATA => $this->getRequestData($transport),
        ]);
    }

    private function isOAuthConfigured(UPSTransport $transport): bool
    {
        return
            !empty($transport->getUpsClientId())
            && !empty($transport->getUpsClientSecret());
    }

    /**
     * @param UPSTransport $transport
     *
     * @return array
     */
    private function getRequestData(UPSTransport $transport): array
    {
        $requestData = [];

        if (!$this->isOAuthConfigured($transport)) {
            $requestData['UPSSecurity'] = [
                'UsernameToken' => [
                    'Username' => $transport->getUpsApiUser(),
                    'Password' => $this->crypter->decryptData($transport->getUpsApiPassword()),
                ],
                'ServiceAccessToken' => [
                    'AccessLicenseNumber' => $transport->getUpsApiKey(),
                ],
            ];
        }

        $requestData['RateRequest'] = [
            'Request' => [
                'RequestOption' => 'Shop'
            ],
            'Shipment' => [
                'Shipper' => [
                    'Name' => 'Company2',
                    'Address' => [
                        'PostalCode' => '10001', // ZipCode should correspond with CountryCode
                        'CountryCode' => $transport->getUpsCountry()->getIso2Code()
                    ]
                ],
                'ShipTo' => [
                    'Name' => 'Company1',
                    'Address' => [
                        'PostalCode' => '10001', // ZipCode should correspond with CountryCode
                        'CountryCode' => $transport->getUpsCountry()->getIso2Code()
                    ]
                ],
                'ShipFrom' => [
                    'Name' => 'Company2',
                    'Address' => [
                        'PostalCode' => '10001', // ZipCode should correspond with CountryCode
                        'CountryCode' => $transport->getUpsCountry()->getIso2Code()
                    ]
                ],
                'Service' => [
                    'Code' => '03' // UPS Ground Shipping
                ],
                'Package' => [
                    0 => [
                        'PackagingType' => [
                            'Code' => '02'
                        ],
                        'PackageWeight' => [
                            'UnitOfMeasurement' => [
                                'Code' => $transport->getUpsUnitOfWeight()
                            ],
                            'Weight' => '10'
                        ]
                    ]
                ]
            ]
        ];

        return $requestData;
    }
}
