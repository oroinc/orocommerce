<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\WebCatalogBundle\EventListener\WebCatalogUsageListener;
use Oro\Bundle\WebCatalogBundle\Provider\CacheableWebCatalogUsageProvider;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogUsageProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class WebCatalogUsageListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|CacheableWebCatalogUsageProvider */
    private $cacheableProvider;

    /** @var WebCatalogUsageListener */
    private $listener;

    protected function setUp(): void
    {
        $this->cacheableProvider = $this->createMock(CacheableWebCatalogUsageProvider::class);

        $this->listener = new WebCatalogUsageListener($this->cacheableProvider);
    }

    public function testOnConfigurationUpdateWhenWebCatalogConfigIsNotChanged()
    {
        $event = $this->createMock(ConfigUpdateEvent::class);

        $event->expects(self::once())
            ->method('isChanged')
            ->with(WebCatalogUsageProvider::SETTINGS_KEY)
            ->willReturn(false);
        $this->cacheableProvider->expects(self::never())
            ->method('clearCache');

        $this->listener->onConfigurationUpdate($event);
    }

    public function testOnConfigurationUpdateWhenWebCatalogConfigIsChanged()
    {
        $event = $this->createMock(ConfigUpdateEvent::class);

        $event->expects(self::once())
            ->method('isChanged')
            ->with(WebCatalogUsageProvider::SETTINGS_KEY)
            ->willReturn(true);
        $this->cacheableProvider->expects(self::once())
            ->method('clearCache');

        $this->listener->onConfigurationUpdate($event);
    }

    public function testOnFlushWhenNoInsertedOrDeletedWebsite()
    {
        $args = $this->createMock(OnFlushEventArgs::class);

        $em = $this->createMock(EntityManager::class);
        $uow = $this->createMock(UnitOfWork::class);

        $args->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([new \stdClass()]);
        $uow->expects(self::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([new \stdClass()]);
        $this->cacheableProvider->expects(self::never())
            ->method('clearCache');

        $this->listener->onFlush($args);
    }

    public function testOnFlushWhenHasInsertedWebsite()
    {
        $args = $this->createMock(OnFlushEventArgs::class);

        $em = $this->createMock(EntityManager::class);
        $uow = $this->createMock(UnitOfWork::class);

        $args->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([$this->createMock(Website::class)]);
        $uow->expects(self::never())
            ->method('getScheduledEntityDeletions');
        $this->cacheableProvider->expects(self::once())
            ->method('clearCache');

        $this->listener->onFlush($args);
    }

    public function testOnFlushWhenHasDeletedWebsite()
    {
        $args = $this->createMock(OnFlushEventArgs::class);

        $em = $this->createMock(EntityManager::class);
        $uow = $this->createMock(UnitOfWork::class);

        $args->expects(self::once())
            ->method('getEntityManager')
            ->willReturn($em);
        $em->expects(self::once())
            ->method('getUnitOfWork')
            ->willReturn($uow);
        $uow->expects(self::once())
            ->method('getScheduledEntityInsertions')
            ->willReturn([]);
        $uow->expects(self::once())
            ->method('getScheduledEntityDeletions')
            ->willReturn([$this->createMock(Website::class)]);
        $this->cacheableProvider->expects(self::once())
            ->method('clearCache');

        $this->listener->onFlush($args);
    }
}
