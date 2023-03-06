<?php

namespace Oro\Bundle\UPSBundle\Tests\Unit\Handler;

use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CacheBundle\Action\DataStorage\InvalidateCacheDataStorage;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;
use Oro\Bundle\UPSBundle\Cache\ShippingPriceCache as UPSShippingPriceCache;
use Oro\Bundle\UPSBundle\Entity\UPSTransport as UPSSettings;
use Oro\Bundle\UPSBundle\Handler\InvalidateCacheActionHandler;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\Factory\TimeInTransitCacheProviderFactoryInterface;
use Oro\Bundle\UPSBundle\TimeInTransit\CacheProvider\TimeInTransitCacheProviderInterface;

class InvalidateCacheActionHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const TRANSPORT_ID = 1;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var TimeInTransitCacheProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $timeInTransitCacheProvider;

    /** @var TimeInTransitCacheProviderFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $timeInTransitCacheProviderFactory;

    /** @var UPSShippingPriceCache|\PHPUnit\Framework\MockObject\MockObject */
    private $upsPriceCache;

    /** @var ShippingPriceCache|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingPriceCache;

    /** @var InvalidateCacheActionHandler */
    private $handler;

    protected function setUp(): void
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

        $this->doctrineHelper->expects(self::any())
            ->method('getEntityRepository')
            ->willReturn($repository);

        $settings = $this->createMock(UPSSettings::class);

        $repository->expects(self::any())
            ->method('find')
            ->with(self::TRANSPORT_ID)
            ->willReturn($settings);

        $this->upsPriceCache->expects(self::once())
            ->method('deleteAll');

        $this->shippingPriceCache->expects(self::once())
            ->method('deleteAllPrices');

        $this->timeInTransitCacheProviderFactory->expects(self::once())
            ->method('createCacheProviderForTransport')
            ->with($settings)
            ->willReturn($this->timeInTransitCacheProvider);

        $this->timeInTransitCacheProvider->expects(self::once())
            ->method('deleteAll');

        $this->handler->handle($dataStorage);
    }
}
