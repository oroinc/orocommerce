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
     * @var FedexRateServiceBySettingsClientInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $restRateServiceClient;

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
        $this->restRateServiceClient = $this->createMock(FedexRateServiceBySettingsClientInterface::class);
        $this->cache = $this->createMock(FedexResponseCacheInterface::class);
        $this->cacheKeyFactory = $this->createMock(FedexResponseCacheKeyFactoryInterface::class);

        $this->client = new FedexRateServiceCachedClient(
            $this->restRateServiceClient,
            $this->rateServiceClient,
            $this->cache,
            $this->cacheKeyFactory
        );
    }

    public function testSendCacheHasResponse(): void
    {
        $request = new FedexRequest('test/uri');
        $settings = new FedexIntegrationSettings();
        $cacheKey = new FedexResponseCacheKey($request, $settings);
        $response = $this->createMock(FedexRateServiceResponseInterface::class);

        $this->cacheKeyFactory
            ->expects(self::once())
            ->method('create')
            ->with($request, $settings)
            ->willReturn($cacheKey);

        $this->cache
            ->expects(self::once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn($response);
        $this->cache
            ->expects(self::never())
            ->method('set');

        self::assertSame($response, $this->client->send($request, $settings));
    }

    public function testSendResponseNotSuccessful(): void
    {
        $request = new FedexRequest('test/uri');
        $settings = new FedexIntegrationSettings();
        $cacheKey = new FedexResponseCacheKey($request, $settings);
        $response = new FedexRateServiceResponse(400);

        $this->cacheKeyFactory
            ->expects(self::once())
            ->method('create')
            ->with($request, $settings)
            ->willReturn($cacheKey);

        $this->cache
            ->expects(self::once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn(null);
        $this->cache
            ->expects(self::never())
            ->method('set');

        $this->rateServiceClient
            ->expects(self::once())
            ->method('send')
            ->willReturn($response);

        self::assertSame($response, $this->client->send($request, $settings));
    }

    public function testSendResponseSuccessful(): void
    {
        $request = new FedexRequest('test/uri');
        $settings = new FedexIntegrationSettings();
        $cacheKey = new FedexResponseCacheKey($request, $settings);
        $response = new FedexRateServiceResponse();

        $this->cacheKeyFactory
            ->expects(self::once())
            ->method('create')
            ->with($request, $settings)
            ->willReturn($cacheKey);

        $this->cache
            ->expects(self::once())
            ->method('get')
            ->with($cacheKey)
            ->willReturn(null);
        $this->cache
            ->expects(self::once())
            ->method('set')
            ->with($cacheKey, $response);

        $this->rateServiceClient
            ->expects(self::once())
            ->method('send')
            ->with($request, $settings)
            ->willReturn($response);

        self::assertSame($response, $this->client->send($request, $settings));
    }
}
