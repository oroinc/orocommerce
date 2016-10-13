<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\Context;

use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

class ContextFactory
{
    /**
     * Context used in reindexation
     *
     * @param ReindexationRequestEvent $event
     * @return array
     */
    public static function createForReindexation(ReindexationRequestEvent $event)
    {
        $context                                            = [];
        $context[AbstractIndexer::CONTEXT_WEBSITE_ID_KEY]   = $event->getWebsitesIds();
        $context[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY] = $event->getIds();

        return $context;
    }
}
