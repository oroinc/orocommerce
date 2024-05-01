<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Unit\Async\Deletion;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Deletion\DeleteOrphanSuggestionsChunkProcessor;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Deletion\DeleteOrphanSuggestionsChunkTopic as Topic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Repository\SuggestionRepository;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Event\SuggestionDeleteEvent;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcher;

final class DeleteOrphanSuggestionsChunkProcessorTest extends \PHPUnit\Framework\TestCase
{
    private ManagerRegistry&MockObject $doctrine;

    private EventDispatcher&MockObject $dispatcher;

    private SuggestionRepository&MockObject $repository;

    private DeleteOrphanSuggestionsChunkProcessor $processor;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->dispatcher = $this->createMock(EventDispatcher::class);
        $this->repository = $this->createMock(SuggestionRepository::class);

        $this->processor = new DeleteOrphanSuggestionsChunkProcessor($this->doctrine, $this->dispatcher);
    }

    public function testThatSuggestionWillBeDeleted(): void
    {
        $ids = [1, 2, 3];

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->willReturn($this->repository);

        $this->repository
            ->expects(self::once())
            ->method('removeSuggestionsByIds')
            ->with(self::equalTo($ids));

        $this->dispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(self::callback(
                static function (SuggestionDeleteEvent $suggestionDeleteEvent) use ($ids) {
                    return $ids === $suggestionDeleteEvent->getDeletedSuggestionIds();
                }
            ));

        $message = new Message();
        $message->setBody([Topic::SUGGESTION_IDS => $ids]);

        $status = $this->processor->process($message, $this->createMock(SessionInterface::class));

        self::assertEquals(MessageProcessorInterface::ACK, $status);
    }

    public function testThatProcessorHasCorrectTopics(): void
    {
        self::assertEquals([Topic::getName()], $this->processor::getSubscribedTopics());
    }
}
