<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Unit\Async\Generation;

use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Generation\PersistSuggestionPhrasesProcessor;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\PersistProductsSuggestionRelationChunkTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\PersistSuggestionPhrasesChunkTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Persister\SuggestionPersister;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Transport\Message as TransportMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;

final class PersistSuggestionPhrasesProcessorTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private PersistSuggestionPhrasesProcessor $processor;

    private MessageProducerInterface&MockObject $producer;

    private SessionInterface&MockObject $session;

    private SuggestionPersister&MockObject $suggestionPersister;

    private int $batchSize = 100;

    #[\Override]
    protected function setUp(): void
    {
        $this->session = $this->createMock(SessionInterface::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->suggestionPersister = $this->createMock(SuggestionPersister::class);

        $this->processor = new PersistSuggestionPhrasesProcessor(
            $this->producer,
            $this->suggestionPersister,
            $this->batchSize
        );
    }

    public function testThatPhrasesPersisted(): void
    {
        $message = new TransportMessage();
        $message->setBody([
            PersistSuggestionPhrasesChunkTopic::ORGANIZATION => 1,
            PersistSuggestionPhrasesChunkTopic::PRODUCTS_WRAPPER => [
                1 => [
                    'phrase1 localization1' => [1, 2],
                    'phrase2_localization1' => [3]
                ],
                2 => [
                    'phrase1_localization2' => [1, 2],
                    'phrase2_localization2' => [3]
                ]
            ]
        ]);

        $this->producer
            ->expects(self::any())
            ->method('send')
            ->with(
                PersistProductsSuggestionRelationChunkTopic::getName(),
                [
                    PersistProductsSuggestionRelationChunkTopic::PRODUCTS_WRAPPER => [
                        1 => [1, 2],
                        3 => [1, 2],
                        4 => [3]
                    ]
                ]
            );

        self::assertEquals(
            $this->processor::ACK,
            $this->processor->process($message, $this->session)
        );
    }

    public function testThatMessageNotProduced(): void
    {
        $message = new TransportMessage();
        $message->setBody([
            PersistSuggestionPhrasesChunkTopic::ORGANIZATION => 1,
            PersistSuggestionPhrasesChunkTopic::PRODUCTS_WRAPPER => [
                1 => [
                    'phrase1 localization1' => [1, 2],
                    'phrase2_localization1' => [3]
                ],
                2 => [
                    'phrase1_localization2' => [1, 2],
                    'phrase2_localization2' => [3]
                ]
            ]
        ]);

        $this->suggestionPersister
            ->expects(self::once())
            ->method('persistSuggestions')
            ->willReturn([]);

        $this->producer
            ->expects(self::never())
            ->method('send');

        self::assertEquals(
            $this->processor::ACK,
            $this->processor->process($message, $this->session)
        );
    }
}
