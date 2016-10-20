<?php

namespace Oro\Bundle\AccountBundle\EventListener;

use Oro\Bundle\AccountBundle\Indexer\ProductVisibilityIndexer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;

class WebsiteSearchProductVisibilityIndexerListener
{
    /**
     * @var ProductVisibilityIndexer
     */
    private $visibilityIndexer;

    /**
     * @param ProductVisibilityIndexer $visibilityIndexer
     */
    public function __construct(ProductVisibilityIndexer $visibilityIndexer)
    {
        $this->visibilityIndexer = $visibilityIndexer;
    }

    /**
     * @param IndexEntityEvent $event
     * @throws \InvalidArgumentException
     */
    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        $context = $event->getContext();
        if (!isset($context[AbstractIndexer::CONTEXT_WEBSITE_ID_KEY])) {
            throw new \InvalidArgumentException('Website id is absent in context');
        }

        $websiteId = $context[AbstractIndexer::CONTEXT_WEBSITE_ID_KEY];
        $this->visibilityIndexer->addIndexInfo($event, $websiteId);
    }
}
