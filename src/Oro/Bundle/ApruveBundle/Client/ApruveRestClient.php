<?php

namespace Oro\Bundle\ApruveBundle\Client;

use Oro\Bundle\ApruveBundle\Client\Exception\UnsupportedMethodException;
use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequestInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;

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
     */
    public function execute(ApruveRequestInterface $apruveRequest)
    {
        $classMethod = $this->getClientClassMethodByRequest($apruveRequest);

        $classMethodArguments = $this->getClientArgumentsByRequest($apruveRequest);

        return call_user_func_array([$this->restClient, $classMethod], $classMethodArguments);
    }

    /**
     * @param ApruveRequestInterface $apruveRequest
     *
     * @return string
     */
    private function getClientClassMethodByRequest(ApruveRequestInterface $apruveRequest)
    {
        $httpMethod = $apruveRequest->getMethod();

        $clientMethodsByHttpMethodArray = $this->getClientClassMethodsByHttpMethodsArray();

        if (!array_key_exists($httpMethod, $clientMethodsByHttpMethodArray)) {
            $msg = sprintf('Rest client does not support method "%s"', $httpMethod);

            throw new UnsupportedMethodException($msg);
        }

        return $clientMethodsByHttpMethodArray[$httpMethod];
    }

    /**
     * @return string[]
     */
    private function getClientClassMethodsByHttpMethodsArray()
    {
        return [
            self::METHOD_GET => 'get',
            self::METHOD_POST => 'post',
            self::METHOD_PUT => 'put',
            self::METHOD_DELETE => 'delete',
        ];
    }

    /**
     * @param ApruveRequestInterface $apruveRequest
     *
     * @return array
     */
    private function getClientArgumentsByRequest(ApruveRequestInterface $apruveRequest)
    {
        if ($apruveRequest->getMethod() === self::METHOD_DELETE) {
            return [$apruveRequest->getUri()];
        }

        return [$apruveRequest->getUri(), $apruveRequest->getData()];
    }
}
