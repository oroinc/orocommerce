<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Tests\Unit\Async\Generation;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\MessageQueueBundle\Client\BufferedMessageProducer;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Generation\GenerateSuggestionPhrasesProcessor;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\GenerateSuggestionsPhrasesChunkTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\GenerateSuggestionsTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Async\Topic\Generation\PersistSuggestionPhrasesChunkTopic;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Repository\ProductSuggestionRepository;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Provider\SuggestionProvider;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message as TransportMessage;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class GenerateSuggestionPhrasesProcessorTest extends TestCase
{
    private GenerateSuggestionPhrasesProcessor $processor;

    private BufferedMessageProducer&MockObject $producer;
    private SuggestionProvider&MockObject $suggestionProvider;
    private ManagerRegistry&MockObject $doctrine;

    private int $batchSize = 100;

    #[\Override]
    protected function setUp(): void
    {
        $this->producer = $this->createMock(BufferedMessageProducer::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->suggestionProvider = $this->createMock(SuggestionProvider::class);

        $this->processor = new GenerateSuggestionPhrasesProcessor(
            $this->producer,
            $this->suggestionProvider,
            $this->doctrine,
            $this->batchSize
        );
    }

    public function testThatPhrasesCreated(): void
    {
        $repository = $this->createMock(ProductSuggestionRepository::class);

        $products = [
            [
                'id' => 1,
                'sku' => 'sku',
                'name' => 'name',
                'localizationId' => null
            ],
            [
                'id' => 2,
                'sku' => 'sku2',
                'name' => 'name2',
                'localizationId' => null
            ],
            [
                'id' => 3,
                'sku' => 'sku',
                'name' => 'name',
                'localizationId' => 1
            ],
        ];

        $this->doctrine
            ->expects(self::once())
            ->method('getRepository')
            ->willReturn($repository);

        $repository
            ->expects(self::once())
            ->method('clearProductSuggestionsByProductIds')
            ->with(\array_column($products, 'id'));

        $this->suggestionProvider
            ->expects(self::once())
            ->method('getLocalizedSuggestionPhrasesGroupedByProductId')
            ->with(\array_column($products, 'id'), $this->batchSize)
            ->willReturn($this->generate([
                0 => [
                    'sku_phrase' => [1],
                    'sku2_phrase' => [2],
                    'name_phrase' => [1],
                    'name_phrase2' => [2]
                ],
                1 => [
                    'sku_phrase' => [3],
                    'name_phrase' => [3],
                ]
            ]));

        $this->producer->expects(self::once())->method('disableBuffering');
        $this->producer->expects(self::once())->method('enableBuffering');

        $this->producer
            ->expects(self::exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    PersistSuggestionPhrasesChunkTopic::getName(),
                    [
                        PersistSuggestionPhrasesChunkTopic::ORGANIZATION => 1,
                        PersistSuggestionPhrasesChunkTopic::PRODUCTS_WRAPPER => [
                            0 => [
                                'sku_phrase' => [1],
                                'sku2_phrase' => [2],
                                'name_phrase' => [1],
                                'name_phrase2' => [2]
                            ]
                        ],
                    ]
                ],
                [
                    PersistSuggestionPhrasesChunkTopic::getName(),
                    [
                        PersistSuggestionPhrasesChunkTopic::ORGANIZATION => 1,
                        PersistSuggestionPhrasesChunkTopic::PRODUCTS_WRAPPER => [
                            1 => [
                                'sku_phrase' => [3],
                                'name_phrase' => [3],
                            ]
                        ]
                    ]
                ]
            );

        $message = new TransportMessage();
        $message->setBody([
            GenerateSuggestionsPhrasesChunkTopic::ORGANIZATION => 1,
            GenerateSuggestionsTopic::PRODUCT_IDS => \array_column($products, 'id')
        ]);

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->createMock(SessionInterface::class))
        );
    }

    private function generate(array $yield_values): \Generator
    {
        foreach ($yield_values as $value) {
            yield $value;
        }
    }
}
