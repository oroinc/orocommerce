<?php

namespace OroB2B\Bundle\MenuBundle\Entity\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Component\DependencyInjection\ServiceLink;

use OroB2B\Bundle\MenuBundle\Entity\MenuItem;
use OroB2B\Bundle\MenuBundle\Menu\DatabaseMenuProvider;

class MenuItemListener
{
    /**
     * @var ServiceLink
     */
    protected $providerLink;

    /**
     * @param ServiceLink $providerLink
     */
    public function __construct(ServiceLink $providerLink)
    {
        $this->providerLink = $providerLink;
    }

    /**
     * @param MenuItem $menuItem
     * @param LifecycleEventArgs $event
     */
    public function postUpdate(MenuItem $menuItem, LifecycleEventArgs $event)
    {
        if ($menuItem->getParent()) {
            $this->rebuildCache($menuItem, $event);
        }
    }

    /**
     * @param MenuItem $menuItem
     * @param LifecycleEventArgs $event
     */
    public function postPersist(MenuItem $menuItem, LifecycleEventArgs $event)
    {
        if ($menuItem->getParent()) {
            $this->rebuildCache($menuItem, $event);
        }
    }

    /**
     * @param MenuItem $menuItem
     * @param LifecycleEventArgs $event
     */
    public function postRemove(MenuItem $menuItem, LifecycleEventArgs $event)
    {
        if ($menuItem->getParent()) {
            $this->rebuildCache($menuItem, $event);
        } else {
            if (!$menuItem->getDefaultTitle()) {
                return;
            }
            $alias = $menuItem->getDefaultTitle()->getString();
            $this->getProvider()->clearCacheByAlias($alias);
        }
    }

    /**
     * @param MenuItem $menuItem
     * @param LifecycleEventArgs $event
     */
    protected function rebuildCache(MenuItem $menuItem, LifecycleEventArgs $event)
    {
        // always get root from parent, because it doesn't exist in the entity on persist
        $rootId = $menuItem->getParent()->getRoot();
        if (!$rootId) {
            return;
        }
        /** @var MenuItem $root */
        $root = $event->getEntityManager()->find('OroB2BMenuBundle:MenuItem', $rootId);
        if (!$root) {
            return;
        }
        $alias = $root->getDefaultTitle()->getString();
        $this->getProvider()->rebuildCacheByAlias($alias);
    }

    /**
     * @return DatabaseMenuProvider
     */
    protected function getProvider()
    {
        return $this->providerLink->getService();
    }
}
