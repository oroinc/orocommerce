<?php

namespace Oro\Bundle\MenuBundle\Entity\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;

use Oro\Component\DependencyInjection\ServiceLink;
use Oro\Bundle\MenuBundle\Entity\MenuItem;
use Oro\Bundle\MenuBundle\Menu\DatabaseMenuProvider;

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
    public function postRemove(MenuItem $menuItem, LifecycleEventArgs $event)
    {
        if ($menuItem->getParent()) {
            $this->getProvider()->rebuildCacheByMenuItem($menuItem);
        } else {
            if (!$menuItem->getDefaultTitle()) {
                return;
            }
            $alias = $menuItem->getDefaultTitle()->getString();
            $this->getProvider()->clearCacheByAlias($alias);
        }
    }

    /**
     * @return DatabaseMenuProvider
     */
    protected function getProvider()
    {
        return $this->providerLink->getService();
    }
}
