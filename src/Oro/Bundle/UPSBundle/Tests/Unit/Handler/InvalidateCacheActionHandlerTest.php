<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Handler;

use Oro\Bundle\CacheBundle\Action\DataStorage\InvalidateCacheDataStorage;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache as UPSShippingPriceCache;
use Oro\Bundle\UPSBundle\Handler\InvalidateCacheActionHandler;

class InvalidateCacheActionHandlerTest extends \PHPUnit_Framework_TestCase
{
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

        $this->handler = new InvalidateCacheActionHandler(
            $this->upsPriceCache,
            $this->shippingPriceCache
        );
    }

    public function testHandle()
    {
        $dataStorage = new InvalidateCacheDataStorage([
            InvalidateCacheActionHandler::PARAM_TRANSPORT_ID => 1
        ]);

        $this->upsPriceCache->expects(static::once())
            ->method('deleteAll');

        $this->shippingPriceCache->expects(static::once())
            ->method('deleteAllPrices');

        $this->handler->handle($dataStorage);
    }
}
