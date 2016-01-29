<?php

namespace OroB2B\Bundle\MenuBundle\EventListener;

use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;

use OroB2B\Bundle\MenuBundle\Menu\DatabaseMenuProvider;

class MenuItemFormHandlerListener
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
     * @param AfterFormProcessEvent $event
     */
    public function afterEntityFlush(AfterFormProcessEvent $event)
    {
        $menuItem = $event->getData();
        if ($menuItem->getParent()) {
            $this->menuProvider->rebuildCacheByMenuItem($menuItem);
        }
    }
}
