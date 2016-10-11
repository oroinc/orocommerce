<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextBuilder;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

class ReindexRequestListener
{
    /**
     * @var IndexerInterface|null
     */
    protected $regularIndexer;

    /**
     * @var IndexerInterface|null
     */
    protected $asyncIndexer;

    /**
     * @param IndexerInterface|null $regularIndexer
     * @param IndexerInterface|null $asyncIndexer
     */
    public function __construct(
        IndexerInterface $regularIndexer = null,
        IndexerInterface $asyncIndexer = null
    ) {
        $this->regularIndexer = $regularIndexer;
        $this->asyncIndexer   = $asyncIndexer;
    }

    /**
     * @param ReindexationRequestEvent $event
     * @throws \LogicException
     */
    public function process(ReindexationRequestEvent $event)
    {
        $indexer = $event->isScheduled() ? $this->asyncIndexer : $this->regularIndexer;
        if ($indexer !== null) {
            $this->processWithIndexer($event, $indexer);
        }
    }

    /**
     * @param ReindexationRequestEvent $event
     * @param IndexerInterface         $indexer
     * @throws \LogicException
     */
    protected function processWithIndexer(ReindexationRequestEvent $event, IndexerInterface $indexer)
    {
        $className = $event->getClassName();
        $ids       = $event->getIds();

        if (null === $className && count($ids) > 0) {
            throw new \LogicException('Event data cannot contains IDs without class name');
        }

        $context = ContextBuilder::createForReindexation($event);
        $indexer->reindex($className, $context);
    }
}
