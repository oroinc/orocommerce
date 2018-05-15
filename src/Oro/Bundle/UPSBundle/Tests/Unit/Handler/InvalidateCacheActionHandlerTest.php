<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Handler;

use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\CacheBundle\Action\DataStorage\InvalidateCacheDataStorage;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache as UPSShippingPriceCache;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;
use Oro\Bundle\UPSBundle\Handler\InvalidateCacheActionHandler;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\Factory\TimeInTransitCacheProviderFactoryInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\TimeInTransitCacheProviderInterface;

class InvalidateCacheActionHandlerTest extends \PHPUnit_Framework_TestCase
{
    const TRANSPORT_ID = 1;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

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
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->upsPriceCache = $this->createMock(UPSShippingPriceCache::class);
        $this->shippingPriceCache = $this->createMock(ShippingPriceCache::class);
        $this->timeInTransitCacheProviderFactory = $this->createMock(TimeInTransitCacheProviderFactoryInterface::class);
        $this->timeInTransitCacheProvider = $this->createMock(TimeInTransitCacheProviderInterface::class);

        $this->handler = new InvalidateCacheActionHandler(
            $this->doctrineHelper,
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

        $repository = $this->createMock(ObjectRepository::class);

        $this->doctrineHelper
            ->method('getEntityRepository')
            ->willReturn($repository);

        $settings = $this->createSettingsMock();

        $repository
            ->method('find')
            ->with(self::TRANSPORT_ID)
            ->willReturn($settings);

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
            ->with($settings)
            ->willReturn($this->timeInTransitCacheProvider);

        $this->timeInTransitCacheProvider
            ->expects(static::once())
            ->method('deleteAll');

        $this->handler->handle($dataStorage);
    }

    /**
     * @return UPSSettings|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createSettingsMock()
    {
        return $this->createMock(UPSSettings::class);
    }
}
