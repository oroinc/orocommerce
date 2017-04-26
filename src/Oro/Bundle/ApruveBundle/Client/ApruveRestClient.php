<?php

namespace Oro\Bundle\ApruveBundle\Client;

use Oro\Bundle\ApruveBundle\Client\Exception\UnsupportedMethodException;
use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequestInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;

class ApruveRestClient implements ApruveRestClientInterface
{
    const METHOD_GET = 'GET';
    const METHOD_POST = 'POST';
    const METHOD_PUT = 'PUT';
    const METHOD_DELETE = 'DELETE';

    /**
     * @var RestClientInterface
     */
    private $restClient;

    /**
     * @param RestClientInterface $restClient
     */
    public function __construct(RestClientInterface $restClient)
    {
        $this->restClient = $restClient;
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
     * @param string $method
     * @param array  $arguments
     *
     * @return RestResponseInterface|null
     */
    private function performRequest($method, $arguments)
    {
        return call_user_func_array([$this->restClient, $method], $arguments);
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
