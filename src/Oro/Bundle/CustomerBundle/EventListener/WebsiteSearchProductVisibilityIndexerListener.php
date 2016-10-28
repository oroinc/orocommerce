<?php

namespace Oro\Bundle\CustomerBundle\EventListener;

use Oro\Bundle\CustomerBundle\Indexer\ProductVisibilityIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;

class WebsiteSearchProductVisibilityIndexerListener
{
    use ContextTrait;

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
     * @throws \LogicException
     */
    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        $context = $event->getContext();
        $websiteId = $this->requireContextCurrentWebsiteId($context);

        $this->visibilityIndexer->addIndexInfo($event, $websiteId);
    }
}
