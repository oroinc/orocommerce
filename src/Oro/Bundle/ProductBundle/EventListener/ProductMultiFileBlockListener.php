<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\UIBundle\Event\BeforeFormRenderEvent;

/**
 * Stops adding additional fields or attributes to forms if they are on the specified pages
 */
class ProductMultiFileBlockListener
{
    private array $pages = [];

    public function setPages(array $pages): void
    {
        $this->pages = $pages;
    }

    public function onBeforeFormRender(BeforeFormRenderEvent $event): void
    {
        if (in_array($event->getPageId(), $this->pages)) {
            $event->stopPropagation();
        }
    }
}
