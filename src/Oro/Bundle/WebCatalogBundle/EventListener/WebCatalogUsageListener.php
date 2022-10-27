<?php

namespace Oro\Bundle\WebCatalogBundle\EventListener;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;
use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\WebCatalogBundle\Provider\CacheableWebCatalogUsageProvider;
use Oro\Bundle\WebCatalogBundle\Provider\WebCatalogUsageProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * Clears the cache of the web catalog usage provider
 * when another web catalog is selected,
 * a new web catalog is created or existing web catalog is deleted.
 */
class WebCatalogUsageListener
{
    /** @var CacheableWebCatalogUsageProvider */
    private $cacheableWebCatalogUsageProvider;

    public function __construct(CacheableWebCatalogUsageProvider $cacheableWebCatalogUsageProvider)
    {
        $this->cacheableWebCatalogUsageProvider = $cacheableWebCatalogUsageProvider;
    }

    public function onConfigurationUpdate(ConfigUpdateEvent $event)
    {
        if ($event->isChanged(WebCatalogUsageProvider::SETTINGS_KEY)) {
            $this->cacheableWebCatalogUsageProvider->clearCache();
        }
    }

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
