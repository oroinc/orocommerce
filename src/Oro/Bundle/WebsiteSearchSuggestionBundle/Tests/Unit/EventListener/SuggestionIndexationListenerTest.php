<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Unit\EventListener;

use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Suggestion;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Event\SuggestionDeleteEvent;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Event\SuggestionPersistEvent;
use Oro\Bundle\WebsiteSearchSuggestionBundle\EventListener\SuggestionIndexationListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class SuggestionIndexationListenerTest extends TestCase
{
    private EventDispatcherInterface&MockObject $eventDispatcher;

    private SuggestionIndexationListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->listener = new SuggestionIndexationListener($this->eventDispatcher);
        $this->listener->setChunkSize(10000);
    }

    public function testThatReindexEventDispatched(): void
    {
        $persistEvent = new SuggestionPersistEvent();
        $deletedEvent = new SuggestionDeleteEvent();

        $persistEvent->setPersistedSuggestionIds([1, 2, 3]);
        $deletedEvent->setDeletedSuggestionIds([1, 2, 3]);

        $this->eventDispatcher
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->with(
                self::callback(function (ReindexationRequestEvent $event) {
                    self::assertEquals([Suggestion::class], $event->getClassesNames());
                    self::assertEquals([1, 2, 3], $event->getIds());
                    self::assertTrue($event->isScheduled());

                    return true;
                }),
                ReindexationRequestEvent::EVENT_NAME
            );

        $this->listener->startWebsiteReindexForPersistedSuggestions($persistEvent);
        $this->listener->startWebsiteReindexForDeletedSuggestions($deletedEvent);
    }

    public function testDispatchReindexWithEmptyData(): void
    {
        $persistEvent = new SuggestionPersistEvent();
        $deletedEvent = new SuggestionDeleteEvent();

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $this->listener->startWebsiteReindexForPersistedSuggestions($persistEvent);
        $this->listener->startWebsiteReindexForDeletedSuggestions($deletedEvent);
    }
}
