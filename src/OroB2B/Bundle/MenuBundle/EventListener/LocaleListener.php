<?php

namespace OroB2B\Bundle\MenuBundle\EventListener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Component\DependencyInjection\ServiceLink;

use OroB2B\Bundle\MenuBundle\Menu\DatabaseMenuProvider;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

class LocaleListener
{
    /**
     * @var ServiceLink
     */
    protected $menuProviderLink;

    /**
     * @param ServiceLink $menuProviderLink
     */
    public function __construct(ServiceLink $menuProviderLink)
    {
        $this->menuProviderLink = $menuProviderLink;
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if (!$this->isLocaleEntity($entity)) {
            return;
        }
        $this->getMenuProvider()->rebuildCacheByLocale($entity);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if (!$this->isLocaleEntity($entity)) {
            return;
        }
        $uow = $args->getEntityManager()->getUnitOfWork();
        $changes = $uow->getEntityChangeSet($entity);
        if (array_key_exists('parentLocale', $changes) === true) {
            $this->getMenuProvider()->rebuildCacheByLocale($entity);
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        if (!$this->isLocaleEntity($entity)) {
            return;
        }
        $this->getMenuProvider()->clearCacheByLocale($entity);
    }

    /**
     * @param object $entity
     * @return bool
     */
    protected function isLocaleEntity($entity)
    {
        return $entity instanceof Locale;
    }

    /**
     * @return DatabaseMenuProvider
     */
    protected function getMenuProvider()
    {
        return $this->menuProviderLink->getService();
    }
}
