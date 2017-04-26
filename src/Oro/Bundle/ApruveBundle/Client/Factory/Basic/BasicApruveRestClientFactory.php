<?php

namespace Oro\Bundle\ApruveBundle\Client\Factory\Basic;

use Oro\Bundle\ApruveBundle\Client\ApruveRestClient;
use Oro\Bundle\ApruveBundle\Client\Factory\ApruveRestClientFactoryInterface;
use Oro\Bundle\ApruveBundle\Client\Url\Provider\ApruveClientUrlProviderInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;

class BasicApruveRestClientFactory implements ApruveRestClientFactoryInterface
{
    /**
     * @internal
     */
    const HEADER_APRUVE_API_KEY = 'Apruve-Api-Key';

    /**
     * @var ApruveClientUrlProviderInterface
     */
    private $urlProvider;

    /**
     * @var RestClientFactoryInterface
     */
    private $integrationRestClientFactory;

    /**
     * @param ApruveClientUrlProviderInterface $urlProvider
     * @param RestClientFactoryInterface       $integrationRestClientFactory
     */
    public function __construct(
        ApruveClientUrlProviderInterface $urlProvider,
        RestClientFactoryInterface $integrationRestClientFactory
    ) {
        $this->urlProvider = $urlProvider;
        $this->integrationRestClientFactory = $integrationRestClientFactory;
    }

    /**
     * {@inheritDoc}
     */
    public function create($apiKey, $isTestMode)
    {
        $baseUrl = $this->getBaseUrl($isTestMode);
        $options = ['headers' => $this->getHeaders($apiKey)];

        $integrationRestClient = $this->getRestClient($baseUrl, $options);

        return new ApruveRestClient($integrationRestClient);
    }

    /**
     * @param bool $isTestMode
     *
     * @return string
     */
    private function getBaseUrl($isTestMode)
    {
        return $this->urlProvider->getApruveUrl($isTestMode);
    }

    /**
     * @param string $url
     * @param array  $options
     *
     * @return RestClientInterface
     */
    private function getRestClient($url, array $options)
    {
        return $this->integrationRestClientFactory->createRestClient($url, $options);
    }

    /**
     * @param string $apiKey
     *
     * @return array
     */
    private function getHeaders($apiKey)
    {
        $headers = ['Accept' => 'application/json'];
        $headers = array_merge($headers, $this->getAuthHeaders($apiKey));

        return $headers;
    }

    /**
     * @param string $apiKey
     *
     * @return array
     */
    private function getAuthHeaders($apiKey)
    {
        return [self::HEADER_APRUVE_API_KEY => $apiKey];
    }
}
