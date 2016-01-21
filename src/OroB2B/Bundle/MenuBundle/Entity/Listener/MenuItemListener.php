<?php

namespace OroB2B\Bundle\MenuBundle\Entity\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use OroB2B\Bundle\MenuBundle\Entity\MenuItem;
use OroB2B\Bundle\MenuBundle\Menu\DatabaseMenuProvider;

class MenuItemListener
{
    /**
     * @var DatabaseMenuProvider
     */
    protected $provider;

    /**
     * @param DatabaseMenuProvider $provider
     */
    public function __construct(DatabaseMenuProvider $provider)
    {
        $this->provider = $provider;
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
    public function postRemove(MenuItem $menuItem, LifecycleEventArgs $event)
    {
        if ($menuItem->getParentMenuItem()) {
            $this->rebuildCache($menuItem, $event);
        } else {
            $alias = $menuItem->getDefaultTitle()->getString();
            $this->provider->clearCacheByAlias($alias);
        }
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
        $this->provider->rebuildCacheByAlias($alias);
    }
}
