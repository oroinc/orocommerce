<?php

namespace Oro\Bundle\UPSBundle\Tests\Behat\Mock\Client\Factory\AddressValidation;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Test\FakeRestClientFactory;
use Oro\Bundle\IntegrationBundle\Test\FakeRestResponse;
use Oro\Bundle\UPSBundle\Client\Factory\UpsClientFactoryInterface;
use Oro\Bundle\UPSBundle\Client\Url\Provider\UpsClientUrlProviderInterface;

final class AddressValidationUpsClientFactoryMock implements UpsClientFactoryInterface
{
    public function __construct(
        private FakeRestClientFactory $restClientFactory,
        private UpsClientUrlProviderInterface $upsClientUrlProvider
    ) {
    }

    public function createUpsClient($isTestMode): RestClientInterface
    {
        $url = $this->upsClientUrlProvider->getUpsUrl($isTestMode);

        $client = $this->restClientFactory->createRestClient($url, []);

        $addresses = $this->getResponseAddresses();

        $client->setDefaultResponse(
            $this->getResponse(array_slice($addresses, 0, 1))
        );

        $client->setResponseList([
            'expanded-view' => $this->getResponse(array_slice($addresses, 0, 2)),
            'short-view' => $this->getResponse($addresses),
            'no-suggests' => []
        ]);

        return $client;
    }

    private function getResponse(array $addresses): FakeRestResponse
    {
        return new FakeRestResponse(
            200,
            [],
            json_encode([
                'XAVResponse' => [
                    'Response' => [
                        'ResponseStatus' => [
                            'Code' => '1',
                            'Description' => 'Success',
                        ],
                    ],
                    'ValidAddressIndicator' => '',
                    'Candidate' => $addresses
                ]
            ]),
        );
    }

    private function getResponseAddresses(): array
    {
        return [
            [
                'AddressKeyFormat' => [
                    'AddressLine' => [
                        '801 SCENIC HWY',
                    ],
                    'PoliticalDivision2' => 'HAINES CITY 1',
                    'PoliticalDivision1' => 'FL',
                    'PostcodePrimaryLow' => '33844',
                    'PostcodeExtendedLow' => '8562',
                    'Region' => 'HAINES CITY FL 33844-8562',
                    'CountryCode' => 'US',
                ],
            ],
            [
                'AddressKeyFormat' => [
                    'AddressLine' => [
                        '801 SCENIC HWY Second',
                    ],
                    'PoliticalDivision2' => 'HAINES CITY 2',
                    'PoliticalDivision1' => 'FL',
                    'PostcodePrimaryLow' => '33845',
                    'PostcodeExtendedLow' => '8562',
                    'Region' => 'HAINES CITY FL 33845-8562',
                    'CountryCode' => 'US',
                ],
            ],
            [
                'AddressKeyFormat' => [
                    'AddressLine' => [
                        '801 SCENIC HWY Third',
                    ],
                    'PoliticalDivision2' => 'HAINES CITY 3',
                    'PoliticalDivision1' => 'FL',
                    'PostcodePrimaryLow' => '33846',
                    'PostcodeExtendedLow' => '8562',
                    'Region' => 'HAINES CITY FL 33846-8562',
                    'CountryCode' => 'US',
                ],
            ],
            [
                'AddressKeyFormat' => [
                    'AddressLine' => [
                        '801 SCENIC HWY Fourth',
                    ],
                    'PoliticalDivision2' => 'HAINES CITY 4',
                    'PoliticalDivision1' => 'FL',
                    'PostcodePrimaryLow' => '33847',
                    'PostcodeExtendedLow' => '8562',
                    'Region' => 'HAINES CITY FL 33847-8562',
                    'CountryCode' => 'US',
                ],
            ],
            [
                'AddressKeyFormat' => [
                    'AddressLine' => [
                        '801 SCENIC HWY Fifth',
                    ],
                    'PoliticalDivision2' => 'HAINES CITY 5',
                    'PoliticalDivision1' => 'FL',
                    'PostcodePrimaryLow' => '33848',
                    'PostcodeExtendedLow' => '8562',
                    'Region' => 'HAINES CITY FL 33848-8562',
                    'CountryCode' => 'US',
                ],
            ],
        ];
    }
}
