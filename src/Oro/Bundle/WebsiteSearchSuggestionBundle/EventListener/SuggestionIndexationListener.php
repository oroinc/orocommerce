<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\EventListener;

use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Suggestion;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Event\SuggestionDeleteEvent;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Event\SuggestionPersistEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Listener handle Suggestion persisting and deleting events to start search reindex
 */
final class SuggestionIndexationListener
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private int $chunkSize
    ) {
    }

    public function startWebsiteReindexForPersistedSuggestions(SuggestionPersistEvent $event): void
    {
        $this->dispatchReindex($event->getPersistedSuggestionIds());
    }

    public function startWebsiteReindexForDeletedSuggestions(SuggestionDeleteEvent $event): void
    {
        $this->dispatchReindex($event->getDeletedSuggestionIds());
    }

    private function dispatchReindex(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        $event = new ReindexationRequestEvent([Suggestion::class], [], $ids, true, null, $this->chunkSize);
        $this->eventDispatcher->dispatch($event, ReindexationRequestEvent::EVENT_NAME);
    }
}
