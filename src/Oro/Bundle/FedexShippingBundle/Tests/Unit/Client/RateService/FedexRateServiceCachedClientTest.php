<?php

namespace Oro\Bundle\FedexShippingBundle\Tests\Unit\Client\RateService;

use Oro\Bundle\FedexShippingBundle\Cache\Factory\FedexResponseCacheKeyFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Cache\FedexResponseCacheInterface;
use Oro\Bundle\FedexShippingBundle\Cache\FedexResponseCacheKey;
use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceBySettingsClientInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceCachedClient;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponse;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Response\FedexRateServiceResponseInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\FedexRequest;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use PHPUnit\Framework\TestCase;

class FedexRateServiceCachedClientTest extends TestCase
{
    /**
     * @var FedexRateServiceBySettingsClientInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $rateServiceClient;

    /**
     * @var FedexResponseCacheInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cache;

    /**
     * @var FedexResponseCacheKeyFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cacheKeyFactory;

    /**
     * @var FedexRateServiceCachedClient
     */
    private $client;

    protected function setUp(): void
    {
        $this->rateServiceClient = $this->createMock(FedexRateServiceBySettingsClientInterface::class);
        $this->cache = $this->createMock(FedexResponseCacheInterface::class);
        $this->cacheKeyFactory = $this->createMock(FedexResponseCacheKeyFactoryInterface::class);

        $this->client = new FedexRateServiceCachedClient(
            $this->rateServiceClient,
            $this->cache,
            $this->cacheKeyFactory
        );
    }

    public function testSendCacheHasResponse()
    {
        $request = new FedexRequest();
        $settings = new FedexIntegrationSettings();
        $cacheKey = new FedexResponseCacheKey($request, $settings);
        $response = $this->createMock(FedexRateServiceResponseInterface::class);

        $this->cacheKeyFactory
            ->expects(static::once())
            ->method('create')
            ->with($request, $settings)
            ->willReturn($cacheKey);

        $this->cache
            ->expects(static::once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn($response);
        $this->cache
            ->expects(static::never())
            ->method('set');

        static::assertSame($response, $this->client->send($request, $settings));
    }

    public function testSendResponseNotSuccessful()
    {
        $request = new FedexRequest();
        $settings = new FedexIntegrationSettings();
        $cacheKey = new FedexResponseCacheKey($request, $settings);
        $response = new FedexRateServiceResponse(FedexRateServiceResponse::SEVERITY_ERROR, 0);

        $this->cacheKeyFactory
            ->expects(static::once())
            ->method('create')
            ->with($request, $settings)
            ->willReturn($cacheKey);

        $this->cache
            ->expects(static::once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn(null);
        $this->cache
            ->expects(static::never())
            ->method('set');

        $this->rateServiceClient
            ->expects(static::once())
            ->method('send')
            ->willReturn($response);

        static::assertSame($response, $this->client->send($request, $settings));
    }

    public function testSendResponseSuccessful()
    {
        $request = new FedexRequest();
        $settings = new FedexIntegrationSettings();
        $cacheKey = new FedexResponseCacheKey($request, $settings);
        $response = new FedexRateServiceResponse(FedexRateServiceResponse::SEVERITY_SUCCESS, 0);

        $this->cacheKeyFactory
            ->expects(static::once())
            ->method('create')
            ->with($request, $settings)
            ->willReturn($cacheKey);

        $this->cache
            ->expects(static::once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn(null);
        $this->cache
            ->expects(static::once())
            ->method('set')
            ->with($cacheKey, $response);

        $this->rateServiceClient
            ->expects(static::once())
            ->method('send')
            ->with($request, $settings)
            ->willReturn($response);

        static::assertSame($response, $this->client->send($request, $settings));
    }
}
