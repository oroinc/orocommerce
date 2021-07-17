<?php

namespace Oro\Bundle\WebsiteSearchBundle\EventListener;

use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging\ReindexMessageGranularizer;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextFactory;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;

class ReindexRequestListener implements OptionalListenerInterface
{
    use OptionalListenerTrait;

    /**
     * @var IndexerInterface|null
     */
    protected $regularIndexer;

    /**
     * @var IndexerInterface|null
     */
    protected $asyncIndexer;

    /**
     * @var ReindexMessageGranularizer
     */
    private $granularizer;

    public function __construct(
        IndexerInterface $regularIndexer = null,
        IndexerInterface $asyncIndexer = null
    ) {
        $this->regularIndexer = $regularIndexer;
        $this->asyncIndexer   = $asyncIndexer;
    }

    public function setReindexMessageGranularizer(ReindexMessageGranularizer $granularizer)
    {
        $this->granularizer = $granularizer;
    }

    /**
     * @throws \LogicException
     */
    public function process(ReindexationRequestEvent $event)
    {
        if (!$this->enabled) {
            return;
        }

        $indexer = $event->isScheduled() ? $this->asyncIndexer : $this->regularIndexer;
        if ($indexer !== null) {
            $this->processWithIndexer($event, $indexer);
        }
    }

    /**
     * @throws \LogicException
     */
    protected function processWithIndexer(ReindexationRequestEvent $event, IndexerInterface $indexer)
    {
        $factory = new ContextFactory();
        $context = $factory->createForReindexation($event);
        if ($event->getIds()) {
            $reindexMsgData = $this->granularizer
                ->process($event->getClassesNames(), $event->getWebsitesIds(), $context);
            foreach ($reindexMsgData as $message) {
                $indexer->reindex($message['class'], $message['context']);
            }
        } else {
            $indexer->reindex($event->getClassesNames(), $context);
        }
    }
}
