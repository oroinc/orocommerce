<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\Context;

use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

class ContextFactory
{
    use ContextTrait;

    /**
     * Context used in reindexation
     *
     * @param ReindexationRequestEvent $event
     * @return array
     */
    public function createForReindexation(ReindexationRequestEvent $event)
    {
        $context = [];
        $context = $this->setContextWebsiteIds($context, $event->getWebsitesIds());
        $context = $this->setContextEntityIds($context, $event->getIds());

        return $context;
    }
}
