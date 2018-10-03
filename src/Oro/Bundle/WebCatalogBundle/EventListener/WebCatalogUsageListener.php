<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\WebCatalogBundle\Provider\CacheableWebCatalogUsageProvider;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogUsageProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;

class WebCatalogUsageListener
{
    /** @var CacheableWebCatalogUsageProvider */
    private $cacheableWebCatalogUsageProvider;

    /**
     * @param CacheableWebCatalogUsageProvider $cacheableWebCatalogUsageProvider
     */
    public function __construct(CacheableWebCatalogUsageProvider $cacheableWebCatalogUsageProvider)
    {
        $this->cacheableWebCatalogUsageProvider = $cacheableWebCatalogUsageProvider;
    }

    /**
     * @param ConfigUpdateEvent $event
     */
    public function onConfigurationUpdate(ConfigUpdateEvent $event)
    {
        if ($event->isChanged(WebCatalogUsageProvider::SETTINGS_KEY)) {
            $this->cacheableWebCatalogUsageProvider->clearCache();
        }
    }

    /**
     * @param OnFlushEventArgs $args
     */
    public function onFlush(OnFlushEventArgs $args)
    {
        if ($this->hasInsertedOrDeletedWebsites($args->getEntityManager()->getUnitOfWork())) {
            $this->cacheableWebCatalogUsageProvider->clearCache();
        }
    }

    /**
     * @param UnitOfWork $uow
     *
     * @return bool
     */
    private function hasInsertedOrDeletedWebsites(UnitOfWork $uow)
    {
        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof Website) {
                return true;
            }
        }
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof Website) {
                return true;
            }
        }

        return false;
    }
}
