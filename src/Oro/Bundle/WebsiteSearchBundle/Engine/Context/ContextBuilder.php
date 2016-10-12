<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine\Context;

use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

class ContextBuilder
{
    /**
     * Context used in reindexation
     *
     * @param ReindexationRequestEvent $event
     * @return array
     */
    public static function createForReindexation(ReindexationRequestEvent $event)
    {
        $context = [];

        if (null !== $event->getWebsiteId()) {
            $context[AbstractIndexer::CONTEXT_WEBSITE_ID_KEY] = $event->getWebsiteId();
        }

        return $context;
    }
}
