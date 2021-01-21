<?php

namespace Oro\Bundle\UPSBundle\Tests\Behat\Mock\Client\Factory\Basic;

use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Test\FakeRestResponse;
use Oro\Bundle\UPSBundle\Client\Factory\UpsClientFactoryInterface;
use Oro\Bundle\UPSBundle\Client\Url\Provider\UpsClientUrlProviderInterface;

class BasicUpsClientFactory implements UpsClientFactoryInterface
{
    private RestClientFactoryInterface $restClientFactory;
    private UpsClientUrlProviderInterface $upsClientUrlProvider;

    public function __construct(
        RestClientFactoryInterface $restClientFactory,
        UpsClientUrlProviderInterface $upsClientUrlProvider
    ) {
        $this->restClientFactory = $restClientFactory;
        $this->upsClientUrlProvider = $upsClientUrlProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function createUpsClient($isTestMode): RestClientInterface
    {
        $url = $this->upsClientUrlProvider->getUpsUrl($isTestMode);

        $date = new \DateTime('now', new \DateTimeZone('GMT'));

        $client = $this->restClientFactory->createRestClient($url, []);
        $client->setResponseList(
            [
                'Rate' => new FakeRestResponse(
                    200,
                    [
                        'Date' => [$date->format(\DateTime::RFC7231)],
                        'Server' => ['Apache'],
                        'X-Frame-Options' => ['DENY'],
                        'X-XSS-Protection' => ['1; mode=block'],
                        'X-Content-Type-Options' => ['nosniff'],
                        'Strict-Transport-Security' => ['max-age=31536000; includeSubDomains'],
                        'Cache-Control' => ['no-store, no-cache'],
                        'Pragma' => ['no-cache'],
                        'APIErrorMsg' => ['Invalid Access License number'],
                        'APIHttpStatus' => ['401'],
                        'APIErrorCode' => ['250003'],
                        'Transfer-Encoding' => ['chunked'],
                        'Content-Type' => ['application/json'],
                    ],
                    \json_encode(
                        [
                            'Fault' => [
                                'faultcode' => 'Client',
                                'faultstring' => 'An exception has been raised as a result of client data.',
                                'detail' => [
                                    'Errors' => [
                                        'ErrorDetail' => [
                                            'Severity' => 'Hard',
                                            'PrimaryErrorCode' => [
                                                'Code' => '111210',
                                                'Description' => 'The requested service is unavailable between the ' .
                                                    'selected locations.'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        JSON_THROW_ON_ERROR
                    )
                ),
            ]
        );

        return $client;
    }
}
