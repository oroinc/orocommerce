<?php

namespace Oro\Bundle\ProductBundle\Search\Reindex;

use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

/**
 * This service help to prepare and make delayed event dispatching to make reindex of products data in search engine.
 */
class DelayedProductReindexManager extends ProductReindexManager
{
    /**
     * @var ReindexationRequestEvent[]
     */
    protected $delayedEvents = [];

    /**
     * Dispatch all collected events
     */
    public function flushReIndexEvents()
    {
        foreach ($this->delayedEvents as $event) {
            $this->dispatcher->dispatch(ReindexationRequestEvent::EVENT_NAME, $event);
        }
        $this->delayedEvents = [];
    }

    /**
     * Put all re-indexation events to internal queue to prevent issue with Messaging/Database race conditions
     * Events from this queue will be flushed on kernel.terminate event
     *
     * @param array    $productIds
     * @param int|null $websiteId
     * @param bool     $isScheduled
     *
     */
    protected function doReindexProducts(array $productIds, $websiteId, $isScheduled)
    {
        $event = $this->getReindexationRequestEvent($productIds, $websiteId, $isScheduled);
        $this->delayedEvents[] = $event;
    }
}
