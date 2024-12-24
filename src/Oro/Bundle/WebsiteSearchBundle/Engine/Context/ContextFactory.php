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
     */
    public function createForReindexation(ReindexationRequestEvent $event): array
    {
        $context = $this->setContextWebsiteIds($context ?? [], $event->getWebsitesIds());
        $context = $this->setContextEntityIds($context, $event->getIds());
        $context = $this->setContextFieldGroups($context, $event->getFieldGroups());

        return $this->setContextBatchSize($context, $event->getBatchSize());
    }
}
