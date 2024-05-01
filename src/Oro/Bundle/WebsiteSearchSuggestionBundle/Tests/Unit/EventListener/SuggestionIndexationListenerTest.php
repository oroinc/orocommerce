<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Unit\EventListener;

use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Suggestion;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Event\SuggestionDeleteEvent;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Event\SuggestionPersistEvent;
use Oro\Bundle\WebsiteSearchSuggestionBundle\EventListener\SuggestionIndexationListener;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class SuggestionIndexationListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    public function testThatReindexEventDispatched(): void
    {
        $persistEvent = new SuggestionPersistEvent();
        $deletedEvent = new SuggestionDeleteEvent();

        $persistEvent->setPersistedSuggestionIds([1, 2, 3]);

        $deletedEvent->setDeletedSuggestionIds([1, 2, 3]);

        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $eventDispatcher
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

        $listener = new SuggestionIndexationListener($eventDispatcher);

        $listener->startWebsiteReindexForPersistedSuggestions($persistEvent);
        $listener->startWebsiteReindexForDeletedSuggestions($deletedEvent);
    }
}
