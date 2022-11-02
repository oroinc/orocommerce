<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\Context;

use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

/**
 * Creates context based on a given ReindexationRequestEvent.
 */
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
        $context = $this->setContextFieldGroups($context, $event->getFieldGroups());

        return $context;
    }
}
