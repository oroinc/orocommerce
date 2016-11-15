<?php

namespace Oro\Bundle\CustomerBundle\EventListener;

use Oro\Bundle\CustomerBundle\Indexer\ProductVisibilityIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;

class WebsiteSearchProductVisibilityIndexerListener
{
    /**
     * @var ProductVisibilityIndexer
     */
    private $visibilityIndexer;

    /**
     * @var WebsiteContextManager
     */
    private $websiteContextManager;

    /**
     * @param ProductVisibilityIndexer $visibilityIndexer
     * @param WebsiteContextManager $websiteContextManager
     */
    public function __construct(
        ProductVisibilityIndexer $visibilityIndexer,
        WebsiteContextManager $websiteContextManager
    ) {
        $this->visibilityIndexer = $visibilityIndexer;
        $this->websiteContextManager = $websiteContextManager;
    }

    /**
     * @param IndexEntityEvent $event
     */
    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        $websiteId = $this->websiteContextManager->getWebsiteId($event->getContext());
        if (!$websiteId) {
            $event->stopPropagation();

            return;
        }

        $this->visibilityIndexer->addIndexInfo($event, $websiteId);
    }
}
