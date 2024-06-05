<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Unit\Async\Generation;

use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Generation\GenerateSuggestionsProcessor;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\GenerateSuggestionsPhrasesChunkTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\GenerateSuggestionsTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Provider\ProductsProvider;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GenerateSuggestionsProcessorTest extends TestCase
{
    private GenerateSuggestionsProcessor $generateSuggestionsProcessor;

    private MessageProducerInterface&MockObject $producer;

    private SessionInterface&MockObject $session;

    private ProductsProvider&MockObject $productProvider;

    private int $batchSize = 100;

    protected function setUp(): void
    {
        $this->session = $this->createMock(SessionInterface::class);
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->productProvider = $this->createMock(ProductsProvider::class);

        $this->generateSuggestionsProcessor = new GenerateSuggestionsProcessor(
            $this->producer,
            $this->productProvider,
            $this->batchSize
        );
    }

    public function testThatMessageNotSendWhenProductsEmpty(): void
    {
        $message = new Message();

        $this->productProvider
            ->expects(self::once())
            ->method('getListOfProductIdAndOrganizationId')
            ->willReturn(new \ArrayIterator([]));

        $this->producer
            ->expects(self::never())
            ->method('send');

        self::assertEquals(
            $this->generateSuggestionsProcessor::ACK,
            $this->generateSuggestionsProcessor->process($message, $this->session)
        );
    }

    public function testThatInitialMessageProcessed(): void
    {
        $message = new Message();

        $this->productProvider
            ->expects(self::once())
            ->method('getListOfProductIdAndOrganizationId')
            ->willReturn(new \ArrayIterator([
                ['id' => 1, 'organizationId' => 1],
                ['id' => 3, 'organizationId' => 1],
                ['id' => 2, 'organizationId' => 2],
            ]));

        $this->producer
            ->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    GenerateSuggestionsPhrasesChunkTopic::getName(),
                    [
                        GenerateSuggestionsPhrasesChunkTopic::ORGANIZATION => 1,
                        GenerateSuggestionsTopic::PRODUCT_IDS => [1, 3]
                    ]
                ],
                [
                    GenerateSuggestionsPhrasesChunkTopic::getName(),
                    [
                        GenerateSuggestionsPhrasesChunkTopic::ORGANIZATION => 2,
                        GenerateSuggestionsTopic::PRODUCT_IDS => [2]
                    ]
                ]
            );

        self::assertEquals(
            $this->generateSuggestionsProcessor::ACK,
            $this->generateSuggestionsProcessor->process($message, $this->session)
        );
    }
}
