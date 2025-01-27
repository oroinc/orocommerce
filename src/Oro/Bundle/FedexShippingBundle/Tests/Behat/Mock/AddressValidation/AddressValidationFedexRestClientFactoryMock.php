<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Behat\Mock\AddressValidation;

use Oro\Bundle\FedexShippingBundle\AddressValidation\Client\FedexAddressValidationClient;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Test\FakeRestResponse;

final class AddressValidationFedexRestClientFactoryMock implements RestClientFactoryInterface
{
    public function __construct(
        private readonly RestClientFactoryInterface $restClientFactory
    ) {
    }

    public function createRestClient($baseUrl, array $defaultOptions): RestClientInterface
    {
        $client = $this->restClientFactory->createRestClient($baseUrl, $defaultOptions);

        $response = new FakeRestResponse(
            200,
            [],
            json_encode([
                'output' => [
                    'alerts' => [],
                    'resolvedAddresses' => [
                        [
                            'city' => 'HAINES CITY',
                            'postalCode' => '33844',
                            'streetLinesToken' => [
                                '801 SCENIC HWY',
                            ]
                        ],
                    ],
                ]
            ]),
        );

        $client->setResponseList(
            [
                FedexAddressValidationClient::ADDRESS_VALIDATION_URI => $response
            ]
        );

        return $client;
    }
}
