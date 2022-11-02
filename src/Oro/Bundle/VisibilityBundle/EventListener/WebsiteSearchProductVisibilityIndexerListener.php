<?php

namespace Oro\Bundle\VisibilityBundle\EventListener;

use Oro\Bundle\VisibilityBundle\Indexer\ProductVisibilityIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;

/**
 * Add visibility related information to search index.
 */
class WebsiteSearchProductVisibilityIndexerListener
{
    use ContextTrait;

    /**
     * @var ProductVisibilityIndexer
     */
    private $visibilityIndexer;

    /**
     * @var WebsiteContextManager
     */
    private $websiteContextManager;

    public function __construct(
        ProductVisibilityIndexer $visibilityIndexer,
        WebsiteContextManager $websiteContextManager
    ) {
        $this->visibilityIndexer = $visibilityIndexer;
        $this->websiteContextManager = $websiteContextManager;
    }

    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        if (!$this->hasContextFieldGroup($event->getContext(), 'visibility')) {
            return;
        }

        $websiteId = $this->websiteContextManager->getWebsiteId($event->getContext());
        if (!$websiteId) {
            $event->stopPropagation();

            return;
        }

        $this->visibilityIndexer->addIndexInfo($event, $websiteId);
    }
}
