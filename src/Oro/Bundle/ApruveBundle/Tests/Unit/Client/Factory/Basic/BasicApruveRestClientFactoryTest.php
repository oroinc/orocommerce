<?php

namespace Oro\Bundle\ApruveBundle\Tests\Unit\Client\Factory\Basic;

use Oro\Bundle\ApruveBundle\Client\ApruveRestClient;
use Oro\Bundle\ApruveBundle\Client\Factory\Basic\BasicApruveRestClientFactory;
use Oro\Bundle\ApruveBundle\Client\Url\Provider\ApruveClientUrlProviderInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientFactoryInterface;
use Oro\Bundle\IntegrationBundle\Provider\Rest\Client\RestClientInterface;

class BasicApruveRestClientFactoryTest extends \PHPUnit_Framework_TestCase
{
    const SAMPLE_URI = '/sample-uri';
    const SAMPLE_API_KEY = 'qwerty12345';

    /**
     * @var ApruveClientUrlProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $urlProvider;

    /**
     * @var RestClientFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $integrationRestClientFactory;

    /**
     * @var BasicApruveRestClientFactory
     */
    private $factory;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->urlProvider = $this->createMock(ApruveClientUrlProviderInterface::class);

        $this->integrationRestClientFactory = $this->createMock(RestClientFactoryInterface::class);

        $this->factory = new BasicApruveRestClientFactory($this->urlProvider, $this->integrationRestClientFactory);
    }

    /**
     * @dataProvider createDataProvider
     *
     * @param bool $isTestMode
     */
    public function testCreate($isTestMode)
    {
        $apiKey = self::SAMPLE_API_KEY;
        $uri = self::SAMPLE_URI;
        $options = [
            'headers' => [
                'Accept' => 'application/json',
                'Apruve-Api-Key' => $apiKey,
            ]
        ];

        $this->urlProvider->expects(static::once())
            ->method('getApruveUrl')
            ->with($isTestMode)
            ->willReturn($uri);

        $integrationRestClient = $this->createIntegrationRestClientMock();

        $this->integrationRestClientFactory->expects(static::once())
            ->method('createRestClient')
            ->with($uri, $options)
            ->willReturn($integrationRestClient);

        $expected = new ApruveRestClient($integrationRestClient);

        $actual = $this->factory->create($apiKey, $isTestMode);

        static::assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function createDataProvider()
    {
        return [
            'test mode' => [
                'isTestMode' => true,
            ],
            'prod mode' => [
                'isTestMode' => false,
            ],
        ];
    }

    /**
     * @return RestClientInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createIntegrationRestClientMock()
    {
        return $this->createMock(RestClientInterface::class);
    }
}
