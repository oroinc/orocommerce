<?php

namespace Oro\Bundle\ApruveBundle\Client;

use Oro\Bundle\ApruveBundle\Apruve\Client\Request\ApruveRequestInterface;
use Oro\Bundle\ApruveBundle\Client\Exception\UnsupportedMethodException;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfig;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Psr\Log\LoggerAwareTrait;

class ApruveRestClient implements ApruveRestClientInterface
{
    use LoggerAwareTrait;

    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';

    const HEADER_APRUVE_API_KEY = 'Apruve-Api-Key';

    const BASE_URL_PROD = 'https://app.apruve.com/api/v4/';
    const BASE_URL_TEST = 'https://test.apruve.com/api/v4/';

    /**
     * @var RestClientFactoryInterface
     */
    private $restClientFactory;

    /**
     * @var RestClientInterface
     */
    private $restClient;

    /**
     * @var ApruveConfig
     */
    private $apruveConfig;

    /**
     * @param ApruveConfig $apruveConfig
     * @param RestClientFactoryInterface $restClientFactory
     */
    public function __construct(ApruveConfig $apruveConfig, RestClientFactoryInterface $restClientFactory)
    {
        $this->restClientFactory = $restClientFactory;
        $this->apruveConfig = $apruveConfig;
    }

    /**
     * {@inheritDoc}
     * @throws \Oro\Bundle\ApruveBundle\Client\Exception\UnsupportedMethodException
     */
    public function execute(ApruveRequestInterface $apruveRequest)
    {
        list($method, $arguments) = $this->getRequestComponents($apruveRequest);

        return $this->performRequest($method, $arguments);
    }

    /**
     * @return RestClientInterface
     */
    private function getRestClient()
    {
        if ($this->restClient === null) {
            $this->restClient = $this
                ->restClientFactory
                ->createRestClient($this->getBaseUrl(), ['headers' => $this->getHeaders()]);
        }

        return $this->restClient;
    }

    /**
     * @return array
     */
    private function getHeaders()
    {
        $headers = ['Accept' => 'application/json'];
        $headers += $this->getAuthHeaders();

        return $headers;
    }

    /**
     * @return array
     */
    private function getAuthHeaders()
    {
        return [self::HEADER_APRUVE_API_KEY => $this->apruveConfig->getApiKey()];
    }

    /**
     * @return string
     */
    private function getBaseUrl()
    {
        if ($this->apruveConfig->isTestMode()) {
            return self::BASE_URL_TEST;
        }

        return self::BASE_URL_PROD;
    }

    /**
     * @param string $method
     * @param array $arguments
     *
     * @return RestResponseInterface|null
     */
    private function performRequest($method, $arguments)
    {
        $restClient = $this->getRestClient();

        $response = null;
        try {
            $response = call_user_func_array([$restClient, $method], $arguments);
        } catch (RestException $e) {
            if ($this->logger) {
                $this->logger->error($e->getMessage());
            }
        }

        return $response;
    }

    /**
     * @param ApruveRequestInterface $apruveRequest
     *
     * @return array
     *
     * @throws \Oro\Bundle\ApruveBundle\Client\Exception\UnsupportedMethodException
     */
    private function getRequestComponents(ApruveRequestInterface $apruveRequest)
    {
        $uri = $apruveRequest->getUri();
        $data = $apruveRequest->getData();

        switch ($apruveRequest->getMethod()) {
            case self::METHOD_GET:
                $method = 'get';
                $arguments = [$uri];
                break;

            case self::METHOD_POST:
                $method = 'post';
                $arguments = [$uri, $data];
                break;

            case self::METHOD_PUT:
                $method = 'put';
                $arguments = [$uri, $data];
                break;

            case self::METHOD_DELETE:
                $method = 'delete';
                $arguments = [$uri];
                break;

            default:
                $msg = sprintf('Rest client does not support method "%s"', $apruveRequest->getMethod());

                throw new UnsupportedMethodException($msg);
        }

        return [$method, $arguments];
    }
}
