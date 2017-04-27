<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Client;

use Oro\Bundle\ApruveBundle\Client\ApruveRestClient;
use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequestInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestResponseInterface;

class ApruveRestClientTest extends \PHPUnit_Framework_TestCase
{
    const SAMPLE_URI = '/sample-uri';
    const SAMPLE_DATA = ['sample_data' => 'foo'];

    /**
     * @var RestClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $restClient;

    /**
     * @var ApruveRestClient
     */
    private $client;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->restClient = $this->createMock(RestClientInterface::class);

        $this->client = new ApruveRestClient($this->restClient);
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param string $method
     */
    public function testExecute($method)
    {
        $response = $this->createMock(RestResponseInterface::class);

        $uri = self::SAMPLE_URI;
        $data = self::SAMPLE_DATA;

        $this->restClient
            ->expects(static::once())
            ->method($method)
            ->with($uri, $data)
            ->willReturn($response);

        $request = $this->createRequest($method, $uri, $data);

        static::assertEquals($response, $this->client->execute($request));
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'GET' => [
                'method' => 'GET',
            ],
            'POST' => [
                'method' => 'POST',
            ],
            'PUT' => [
                'method' => 'PUT',
            ],
        ];
    }

    public function testDelete()
    {
        $response = $this->createMock(RestResponseInterface::class);

        $method = ApruveRestClient::METHOD_DELETE;
        $uri = self::SAMPLE_URI;

        $this->restClient
            ->expects(static::once())
            ->method($method)
            ->with($uri)
            ->willReturn($response);

        $request = $this->createRequest($method, $uri, []);

        static::assertEquals($response, $this->client->execute($request));
    }

    /**
     * @expectedException \Oro\Bundle\ApruveBundle\Client\Exception\UnsupportedMethodException
     * @expectedExceptionMessage Rest client does not support method "UNSUPPORTED"
     */
    public function testExecuteWithUnsupportedMethod()
    {
        $method = 'UNSUPPORTED';
        $request = $this->createRequest($method, self::SAMPLE_URI, self::SAMPLE_DATA);
        $this->client->execute($request);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Any exception
     */
    public function testExecuteDoesNotCatchAnyException()
    {
        $method = ApruveRestClient::METHOD_GET;
        $uri = self::SAMPLE_URI;

        $this->restClient
            ->expects(static::once())
            ->method($method)
            ->with($uri)
            ->willThrowException(new \Exception('Any exception'));

        $request = $this->createRequest($method, $uri, []);
        $this->client->execute($request);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array  $data
     *
     * @return ApruveRequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createRequest($method, $uri, array $data)
    {
        $request = $this->createMock(ApruveRequestInterface::class);
        $request
            ->method('getMethod')
            ->willReturn($method);
        $request
            ->method('getUri')
            ->willReturn($uri);
        $request
            ->method('getData')
            ->willReturn($data);

        return $request;
    }
}
