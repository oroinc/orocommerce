<?php

namespace OroB2B\Bundle\MenuBundle\Entity\Listener;

use OroB2B\Bundle\MenuBundle\Entity\MenuItem;
use Doctrine\ORM\Event\LifecycleEventArgs;
use OroB2B\Bundle\MenuBundle\Menu\DatabaseMenuProvider;

class MenuItemListener
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
     * @param MenuItem $menuItem
     * @param LifecycleEventArgs $event
     */
    public function postUpdate(MenuItem $menuItem, LifecycleEventArgs $event)
    {
        $this->rebuildCache($menuItem, $event);
    }

    /**
     * @param MenuItem $menuItem
     * @param LifecycleEventArgs $event
     */
    public function postPersist(MenuItem $menuItem, LifecycleEventArgs $event)
    {
        $this->rebuildCache($menuItem, $event);
    }

    /**
     * @param MenuItem $menuItem
     * @param LifecycleEventArgs $event
     */
    protected function rebuildCache(MenuItem $menuItem, LifecycleEventArgs $event)
    {
        $rootId = $menuItem->getRoot();
        if (!$rootId) {
            return;
        }
        /** @var MenuItem $root */
        $root = $event->getEntityManager()->find('OroB2BMenuBundle:MenuItem', $rootId);

        $alias = $root->getDefaultTitle()->getString();
        $this->menuProvider->rebuildCacheByAlias($alias);
    }
    // @todo consider clear cache by root alias on delete
}
