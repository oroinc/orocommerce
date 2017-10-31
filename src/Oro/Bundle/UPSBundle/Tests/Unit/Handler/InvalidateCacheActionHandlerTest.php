<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Handler;

use Oro\Bundle\CacheBundle\Action\DataStorage\InvalidateCacheDataStorage;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache as UPSShippingPriceCache;
use Oro\Bundle\UPSBundle\Handler\InvalidateCacheActionHandler;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\Factory\TimeInTransitCacheProviderFactoryInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\TimeInTransitCacheProviderInterface;

class InvalidateCacheActionHandlerTest extends \PHPUnit_Framework_TestCase
{
    const TRANSPORT_ID = 1;
    /**
     * @var TimeInTransitCacheProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $timeInTransitCacheProvider;

    /**
     * @var TimeInTransitCacheProviderFactoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $timeInTransitCacheProviderFactory;

    /**
     * @var UPSShippingPriceCache|\PHPUnit_Framework_MockObject_MockObject
     */
    private $upsPriceCache;

    /**
     * @var ShippingPriceCache|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingPriceCache;

    /**
     * @var InvalidateCacheActionHandler
     */
    private $handler;

    protected function setUp()
    {
        $this->upsPriceCache = $this->createMock(UPSShippingPriceCache::class);
        $this->shippingPriceCache = $this->createMock(ShippingPriceCache::class);
        $this->timeInTransitCacheProviderFactory = $this->createMock(TimeInTransitCacheProviderFactoryInterface::class);
        $this->timeInTransitCacheProvider = $this->createMock(TimeInTransitCacheProviderInterface::class);

        $this->handler = new InvalidateCacheActionHandler(
            $this->upsPriceCache,
            $this->shippingPriceCache,
            $this->timeInTransitCacheProviderFactory
        );
    }

    public function testHandle()
    {
        $dataStorage = new InvalidateCacheDataStorage([
            InvalidateCacheActionHandler::PARAM_TRANSPORT_ID => self::TRANSPORT_ID,
        ]);

        $this->upsPriceCache
            ->expects(static::once())
            ->method('deleteAll')
            ->with(self::TRANSPORT_ID);

        $this->shippingPriceCache
            ->expects(static::once())
            ->method('deleteAllPrices');

        $this->timeInTransitCacheProviderFactory
            ->expects(static::once())
            ->method('createCacheProviderForTransport')
            ->with(self::TRANSPORT_ID)
            ->willReturn($this->timeInTransitCacheProvider);

        $this->timeInTransitCacheProvider
            ->expects(static::once())
            ->method('deleteAll');

        $this->handler->handle($dataStorage);
    }
}
