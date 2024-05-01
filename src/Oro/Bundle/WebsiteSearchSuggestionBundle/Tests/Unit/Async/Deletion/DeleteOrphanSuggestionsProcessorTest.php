<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Unit\Async\Deletion;

use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Deletion\DeleteOrphanSuggestionsProcessor;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Deletion\DeleteOrphanSuggestionsChunkTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Deletion\DeleteOrphanSuggestionsTopic as Topic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Repository\SuggestionRepository;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Symfony\Bridge\Doctrine\ManagerRegistry;

final class DeleteOrphanSuggestionsProcessorTest extends \PHPUnit\Framework\TestCase
{
    private MessageProducerInterface&MockObject $producer;

    private SuggestionRepository&MockObject $suggestionRepository;

    private ManagerRegistry&Stub $doctrine;

    private DeleteOrphanSuggestionsProcessor $processor;

    protected function setUp(): void
    {
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->doctrine = $this->createStub(ManagerRegistry::class);
        $this->suggestionRepository = $this->createMock(SuggestionRepository::class);

        $this->processor = new DeleteOrphanSuggestionsProcessor(
            $this->producer,
            $this->doctrine,
            5
        );
    }

    public function testThatMessageProcessed(): void
    {
        $this->doctrine
            ->method('getRepository')
            ->willReturn($this->suggestionRepository);

        $this->suggestionRepository
            ->expects(self::once())
            ->method('getSuggestionIdsWithEmptyProducts')
            ->willReturn([1, 2, 3, 4, 5, 6, 7, 8]);

        $this->producer
            ->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    DeleteOrphanSuggestionsChunkTopic::getName(), [
                        DeleteOrphanSuggestionsChunkTopic::SUGGESTION_IDS => [1, 2, 3, 4, 5]
                    ]
                ],
                [
                    DeleteOrphanSuggestionsChunkTopic::getName(), [
                        DeleteOrphanSuggestionsChunkTopic::SUGGESTION_IDS => [6, 7, 8]
                    ]
                ]
            );

        $status = $this->processor->process(
            $this->createMock(MessageInterface::class),
            $this->createMock(SessionInterface::class)
        );

        self::assertEquals(MessageProcessorInterface::ACK, $status);
    }

    public function testThatProcessorHasCorrectTopics(): void
    {
        self::assertEquals([Topic::getName()], $this->processor::getSubscribedTopics());
    }
}
