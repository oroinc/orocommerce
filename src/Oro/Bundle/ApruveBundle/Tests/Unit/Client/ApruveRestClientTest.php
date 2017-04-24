<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Client;

use Oro\Bundle\ApruveBundle\Client\ApruveRestClient;
use Oro\Bundle\ApruveBundle\Client\Request\ApruveRequestInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Exception\RestException;
use Psr\Log\LoggerInterface;

class ApruveRestClientTest extends \PHPUnit_Framework_TestCase
{
    const SAMPLE_URI = '/sample-uri';
    const SAMPLE_DATA = ['sample_data' => 'foo'];
    const API_KEY = 'sampleApiKey';
    const OPTIONS = [
        'headers' => [
            'Accept' => 'application/json',
            ApruveRestClient::HEADER_APRUVE_API_KEY => self::API_KEY,
        ],
    ];

    /**
     * @var RestClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $restClient;

    /**
     * @var ApruveRestClient
     */
    private $client;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var RestClientFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $restClientFactory;

    /**
     * @var ApruveConfigInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $apruveConfig;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->apruveConfig = $this->createMock(ApruveConfigInterface::class);
        $this->apruveConfig
            ->method('getApiKey')
            ->willReturn(self::API_KEY);

        $this->restClient = $this->createMock(RestClientInterface::class);
        $this->restClientFactory = $this->createMock(RestClientFactoryInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->client = new ApruveRestClient($this->apruveConfig, $this->restClientFactory, $this->logger);
    }

    /**
     * @dataProvider executeDataProvider
     *
     * @param bool $testMode
     * @param string $baseUrl
     * @param string $method
     * @param string $uri
     * @param array $data
     */
    public function testExecute($testMode, $baseUrl, $method, $uri, array $data)
    {
        $this->apruveConfig
            ->expects(static::once())
            ->method('isTestMode')
            ->willReturn($testMode);

        $this->restClientFactory
            ->expects(static::once())
            ->method('createRestClient')
            ->with($baseUrl, self::OPTIONS)
            ->willReturn($this->restClient);

        $this->restClient
            ->expects(static::once())
            ->method($method)
            ->with($uri, $data);

        $request = $this->createRequest($method, $uri, $data);
        $this->client->execute($request);
    }

    /**
     * @return array
     */
    public function executeDataProvider()
    {
        return [
            'GET with test mode on' => [
                'testMode' => true,
                'baseUrl' => ApruveRestClient::BASE_URL_TEST,
                'method' => ApruveRestClient::METHOD_GET,
                'uri' => self::SAMPLE_URI,
                'data' => [],
            ],
            'GET with test mode off' => [
                'testMode' => false,
                'baseUrl' => ApruveRestClient::BASE_URL_PROD,
                'method' => ApruveRestClient::METHOD_GET,
                'uri' => self::SAMPLE_URI,
                'data' => [],
            ],
            'POST with test mode on' => [
                'testMode' => true,
                'baseUrl' => ApruveRestClient::BASE_URL_TEST,
                'method' => ApruveRestClient::METHOD_POST,
                'uri' => self::SAMPLE_URI,
                'data' => self::SAMPLE_DATA,
            ],
            'POST with test mode off' => [
                'testMode' => false,
                'baseUrl' => ApruveRestClient::BASE_URL_PROD,
                'method' => ApruveRestClient::METHOD_POST,
                'uri' => self::SAMPLE_URI,
                'data' => self::SAMPLE_DATA,
            ],
            'PUT with test mode on' => [
                'testMode' => true,
                'baseUrl' => ApruveRestClient::BASE_URL_TEST,
                'method' => ApruveRestClient::METHOD_PUT,
                'uri' => self::SAMPLE_URI,
                'data' => self::SAMPLE_DATA,
            ],
            'PUT with test mode off' => [
                'testMode' => false,
                'baseUrl' => ApruveRestClient::BASE_URL_PROD,
                'method' => ApruveRestClient::METHOD_PUT,
                'uri' => self::SAMPLE_URI,
                'data' => self::SAMPLE_DATA,
            ],
            'DELETE with test mode on' => [
                'testMode' => true,
                'baseUrl' => ApruveRestClient::BASE_URL_TEST,
                'method' => ApruveRestClient::METHOD_DELETE,
                'uri' => self::SAMPLE_URI,
                'data' => [],
            ],
            'DELETE with test mode off' => [
                'testMode' => false,
                'baseUrl' => ApruveRestClient::BASE_URL_PROD,
                'method' => ApruveRestClient::METHOD_DELETE,
                'uri' => self::SAMPLE_URI,
                'data' => [],
            ],
        ];
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

    public function testExecuteWithRestException()
    {
        $this->apruveConfig
            ->expects(static::once())
            ->method('isTestMode')
            ->willReturn(true);

        $this->restClientFactory
            ->expects(static::once())
            ->method('createRestClient')
            ->with(ApruveRestClient::BASE_URL_TEST, self::OPTIONS)
            ->willReturn($this->restClient);

        $method = 'POST';
        $msg = 'Sample error message';
        $this->restClient
            ->expects(static::once())
            ->method($method)
            ->with(self::SAMPLE_URI, self::SAMPLE_DATA)
            ->willThrowException(new RestException($msg));

        $this->logger
            ->expects(static::once())
            ->method('error')
            ->with($msg);

        $request = $this->createRequest($method, self::SAMPLE_URI, self::SAMPLE_DATA);
        $actual = $this->client->execute($request);

        static::assertNull($actual);
    }

    public function testExecuteWithSameRestClient()
    {
        $this->apruveConfig
            ->expects(static::once())
            ->method('isTestMode')
            ->willReturn(false);

        $this->restClientFactory
            ->expects(static::once())
            ->method('createRestClient')
            ->with(ApruveRestClient::BASE_URL_PROD, self::OPTIONS)
            ->willReturn($this->restClient);

        $method = 'POST';
        $this->restClient
            ->expects(static::exactly(2))
            ->method($method)
            ->with(self::SAMPLE_URI, self::SAMPLE_DATA);

        $request = $this->createRequest($method, self::SAMPLE_URI, self::SAMPLE_DATA);

        $prop = new \ReflectionProperty(ApruveRestClient::class, 'restClient');
        $prop->setAccessible(true);

        $this->client->execute($request);
        $expectedRestClient = $prop->getValue($this->client);

        $this->client->execute($request);
        $actualRestClient = $prop->getValue($this->client);

        static::assertSame($expectedRestClient, $actualRestClient);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $data
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
