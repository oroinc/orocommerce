<?php

namespace OroB2B\Bundle\MenuBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Event\LifecycleEventArgs;

use OroB2B\Bundle\MenuBundle\Menu\DatabaseMenuProvider;

class LocaleListener
{
    /**
     * @var DatabaseMenuProvider
     */
    protected $menuProvider;

    /**
     * @param DatabaseMenuProvider $menuProvider
     */
    public function __construct(DatabaseMenuProvider $menuProvider)
    {
        $this->menuProvider = $menuProvider;
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
        $this->menuProvider->rebuildCacheByLocale($entity);
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
            $this->menuProvider->rebuildCacheByLocale($entity);
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
        $this->menuProvider->clearCacheByLocale($entity);
    }

    /**
     * @param object $entity
     * @return bool
     */
    protected function isLocaleEntity($entity)
    {
        return ClassUtils::getClass($entity) === 'OroB2B\Bundle\WebsiteBundle\Entity\Locale';
    }
}
