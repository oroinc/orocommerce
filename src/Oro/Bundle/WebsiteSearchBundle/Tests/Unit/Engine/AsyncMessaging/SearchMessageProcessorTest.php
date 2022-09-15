<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine\AsyncMessaging;

use Doctrine\Common\Proxy\Proxy;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ManagerRegistry;
use Monolog\Logger;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Provider\SearchMappingProvider;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;
use Oro\Bundle\WebsiteBundle\Provider\WebsiteProviderInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging\ReindexMessageGranularizer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging\SearchMessageProcessor;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator;
use Oro\Bundle\WebsiteSearchBundle\Event\SearchProcessingEngineExceptionEvent;
use Oro\Component\MessageQueue\Client\Config as MessageQueConfig;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Exception\JobRuntimeException;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SearchMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    private IndexerInterface|\PHPUnit\Framework\MockObject\MockObject $indexer;

    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $messageProducer;

    private ReindexMessageGranularizer|\PHPUnit\Framework\MockObject\MockObject $reindexMessageGranularizer;

    private SearchMessageProcessor $processor;

    private SessionInterface|\PHPUnit\Framework\MockObject\MockObject $session;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    private JobRunner|\PHPUnit\Framework\MockObject\MockObject $jobRunner;

    protected function setUp(): void
    {
        $this->indexer = $this->createMock(IndexerInterface::class);
        $this->indexer
            ->expects(self::any())
            ->method('reindex')
            ->willReturn(1);

        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->reindexMessageGranularizer = $this->createMock(ReindexMessageGranularizer::class);
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->processor = new SearchMessageProcessor(
            $this->indexer,
            $this->messageProducer,
            $this->createIndexerInputValidator(),
            $this->reindexMessageGranularizer,
            $this->jobRunner,
            $this->logger,
            $eventDispatcher
        );

        $this->session = $this->createMock(SessionInterface::class);
        $eventDispatcher
            ->expects(self::any())
            ->method('dispatch')
            ->willReturnCallback(function ($event, string $name) {
                if ($event instanceof SearchProcessingEngineExceptionEvent) {
                    $event->setConsumptionResult(MessageProcessorInterface::REQUEUE);
                }

                return $event;
            });
    }

    private function createIndexerInputValidator(): IndexerInputValidator
    {
        $websiteProvider = $this->createMock(WebsiteProviderInterface::class);
        $mappingProvider = $this->createMock(SearchMappingProvider::class);
        $mappingProvider
            ->expects(self::any())
            ->method('isClassSupported')
            ->willReturnCallback(fn ($class) => class_exists($class, true));

        $indexerInputValidator = new IndexerInputValidator($websiteProvider, $mappingProvider);

        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $indexerInputValidator->setManagerRegistry($managerRegistry);

        $entityManager = $this->createMock(EntityManager::class);
        $managerRegistry
            ->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $reference = $this->createMock(Proxy::class);
        $entityManager
            ->expects(self::any())
            ->method('getReference')
            ->willReturn($reference);

        return $indexerInputValidator;
    }

    public function testProcessDelayedMessage(): void
    {
        $messageBody = ['jobId' => 1];

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);

        $message->expects(self::once())
            ->method('getBody')
            ->willReturn(json_encode($messageBody));

        $message->expects(self::any())
            ->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn(AsyncIndexer::TOPIC_REINDEX);

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturn(true);

        self::assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $this->session));
    }

    public function testProcessDelayedMessageWhenBodyHasInvalidOption(): void
    {
        $messageBody = ['jobId' => 1, 'invalid_key' => 'invalid_value'];

        $message = $this->createMock(MessageInterface::class);

        $message->expects(self::once())
            ->method('getBody')
            ->willReturn($messageBody);

        $message->expects(self::any())
            ->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn(AsyncIndexer::TOPIC_REINDEX);

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function (int $jobId, callable $callable) use ($messageBody) {
                self::assertSame($messageBody['jobId'], $jobId);

                return $callable();
            });

        $exception = new UndefinedOptionsException(
            'The option "invalid_key" does not exist. Defined options are: "class", "context", "granulize".'
        );
        $this->logger
            ->expects(self::once())
            ->method('log')
            ->with(Logger::ERROR, 'An unexpected exception occurred during indexation', ['exception' => $exception]);

        self::assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $this->session));
    }

    public function testProcessDelayedMessageWhenNullReturned(): void
    {
        $messageBody = ['jobId' => 1, 'invalid_key' => 'invalid_value'];

        $message = $this->createMock(MessageInterface::class);

        $message->expects(self::once())
            ->method('getBody')
            ->willReturn($messageBody);

        $message->expects(self::any())
            ->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn(AsyncIndexer::TOPIC_REINDEX);

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function (int $jobId, callable $callable) use ($messageBody) {
                self::assertSame($messageBody['jobId'], $jobId);

                return null;
            });

        self::assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $this->session));
    }

    public function testProcessDelayedMessageWhenJobRuntimeException(): void
    {
        $messageBody = ['jobId' => 1, 'invalid_key' => 'invalid_value'];

        $message = $this->createMock(MessageInterface::class);

        $message->expects(self::once())
            ->method('getBody')
            ->willReturn($messageBody);

        $message->expects(self::any())
            ->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn(AsyncIndexer::TOPIC_REINDEX);

        $jobRuntimeException = new JobRuntimeException(
            sprintf('An error occurred while running job, id: %d', $messageBody['jobId']),
            0,
            new \Exception()
        );

        $this->jobRunner->expects(self::once())
            ->method('runDelayed')
            ->willReturnCallback(function (int $jobId, callable $callable) use ($messageBody, $jobRuntimeException) {
                self::assertSame($messageBody['jobId'], $jobId);

                throw $jobRuntimeException;
            });

        $this->logger
            ->expects(self::once())
            ->method('log')
            ->with(
                Logger::WARNING,
                'An unexpected exception occurred during indexation',
                ['exception' => $jobRuntimeException]
            );

        self::assertEquals(MessageProcessorInterface::REQUEUE, $this->processor->process($message, $this->session));
    }

    /**
     * @dataProvider processingMessageDataProvider
     */
    public function testProcessingMessage($messageBody, $topic, $expectedMethod): void
    {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message
            ->expects(self::once())
            ->method('getBody')
            ->willReturn(JSON::encode($messageBody));
        $message
            ->expects(self::any())
            ->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn($topic);

        $this->indexer
            ->expects(self::once())
            ->method($expectedMethod);

        $this->jobRunner
            ->expects(self::never())
            ->method('runDelayed');

        self::assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $this->session));
    }

    /**
     * @dataProvider processingReindexWithGranulizeDataProvider
     */
    public function testProcessingReindexWithGranulize(
        array $messageBody,
        array $classesToIndex,
        array $websiteIdsToIndex,
        array $granulizedMessages
    ): void {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message
            ->expects(self::once())
            ->method('getBody')
            ->willReturn(JSON::encode($messageBody));

        $message
            ->expects(self::any())
            ->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn(AsyncIndexer::TOPIC_REINDEX);

        $this->reindexMessageGranularizer
            ->expects(self::once())
            ->method('process')
            ->with($classesToIndex, $websiteIdsToIndex, $messageBody['context'])
            ->willReturn($granulizedMessages);

        $this->indexer
            ->expects(self::exactly(count($granulizedMessages)))
            ->method('reindex');

        $this->messageProducer
            ->expects(self::never())
            ->method('send');

        $this->jobRunner
            ->expects(self::never())
            ->method('runDelayed');

        self::assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $this->session));
    }

    /**
     * @dataProvider processingReindexWithGranulizeAsyncDataProvider
     */
    public function testProcessingReindexWithGranulizeAsync(
        array $messageBody,
        array $classesToIndex,
        array $websiteIdsToIndex,
        array $granulizedMessages
    ): void {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message
            ->expects(self::once())
            ->method('getBody')
            ->willReturn(JSON::encode($messageBody));
        $message
            ->expects(self::any())
            ->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn(AsyncIndexer::TOPIC_REINDEX);

        $this->reindexMessageGranularizer
            ->expects(self::once())
            ->method('process')
            ->with($classesToIndex, $websiteIdsToIndex, $messageBody['context'])
            ->willReturn($granulizedMessages);

        $this->indexer
            ->expects(self::never())
            ->method('reindex');

        $this->messageProducer
            ->expects(self::exactly(count($granulizedMessages)))
            ->method('send');

        $this->jobRunner
            ->expects(self::never())
            ->method('runDelayed');

        self::assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $this->session));
    }

    public function testNotRunUniqueWhenNoInputGiven(): void
    {
        $messageBody = ['class' => null, 'context' => []];

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message
            ->expects(self::once())
            ->method('getBody')
            ->willReturn(JSON::encode($messageBody));
        $message
            ->expects(self::any())
            ->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn(AsyncIndexer::TOPIC_REINDEX);

        $this->jobRunner
            ->expects(self::never())
            ->method('runDelayed');

        $this->processor->process($message, $this->session);
    }

    public function testRejectOnUnsupportedTopic(): void
    {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message
            ->expects(self::once())
            ->method('getBody')
            ->willReturn(JSON::encode(['body']));
        $message
            ->expects(self::any())
            ->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn('unsupported-topic');

        $this->jobRunner
            ->expects(self::never())
            ->method('runDelayed');

        self::assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $this->session));
    }

    public function processingMessageDataProvider(): array
    {
        return [
            'save' => [
                'message' => [
                    'entity' => [[
                        'class' => TestActivity::class,
                        'id' => 13,
                    ]],
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [1, 2],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                    ],
                ],
                'topic' => AsyncIndexer::TOPIC_SAVE,
                'expectedMethod' => 'save',
            ],
            'delete' => [
                'message' => [
                    'entity' => [[
                        'class' => TestActivity::class,
                        'id' => 13,
                    ]],
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [1, 2],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                    ],
                ],
                'topic' => AsyncIndexer::TOPIC_DELETE,
                'expectedMethod' => 'delete',
            ],
            'reindex' => [
                'message' => [
                    'class' => TestActivity::class,
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [1, 2],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                    ],
                ],
                'topic' => AsyncIndexer::TOPIC_REINDEX,
                'expectedMethod' => 'reindex',
            ],
            'reindex_with_given_context' => [
                'message' => [
                    'class' => TestActivity::class,
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [1, 2],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                    ],
                ],
                'topic' => AsyncIndexer::TOPIC_REINDEX,
                'expectedMethod' => 'reindex',
            ],
            'resetReindex' => [
                'message' => [
                    'class' => TestActivity::class,
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [1, 2],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                    ],
                ],
                'topic' => AsyncIndexer::TOPIC_RESET_INDEX,
                'expectedMethod' => 'resetIndex',
            ],
        ];
    }

    public function processingReindexWithGranulizeDataProvider(): array
    {
        return [
            'reindex immediately if there are less messages than the batch size on 2 websites and 1 entity' => [
                'message' => [
                    'granulize' => true,
                    'class' => [TestActivity::class],
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [1, 2],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                    ],
                ],
                'classesToIndex' => [TestActivity::class],
                'websiteIdsToIndex' => [1, 2],
                'granulizedMessages' => [
                    [
                        'class' => TestActivity::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                        ],
                    ],
                    [
                        'class' => TestActivity::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                        ],
                    ],
                ],
            ],
            'reindex immediately if there are less messages than the batch size on 3 websites and 2 entities' => [
                'message' => [
                    'granulize' => true,
                    'class' => [Product::class, Category::class],
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [1, 2, 3],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                    ],
                ],
                'classesToIndex' => [Product::class, Category::class],
                'websiteIdsToIndex' => [1, 2, 3],
                'granulizedMessages' => [
                    [
                        'class' => Product::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                        ],
                    ],
                    [
                        'class' => Product::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                        ],
                    ],
                    [
                        'class' => Product::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [3],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                        ],
                    ],
                    [
                        'class' => Category::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                        ],
                    ],
                    [
                        'class' => Category::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                        ],
                    ],
                    [
                        'class' => Category::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [3],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function processingReindexWithGranulizeAsyncDataProvider(): array
    {
        return [
            'reindex asynchronously if there are more messages than the batch size on 2 websites and 1 entity' => [
                'message' => [
                    'granulize' => true,
                    'class' => [TestActivity::class],
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [1, 2],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                    ],
                ],
                'classesToIndex' => [TestActivity::class],
                'websiteIdsToIndex' => [1, 2],
                'granulizedMessages' => [
                    [
                        'class' => Product::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2],
                        ],
                    ],
                    [
                        'class' => Product::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [3],
                        ],
                    ],
                    [
                        'class' => Product::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2],
                        ],
                    ],
                    [
                        'class' => Product::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [3],
                        ],
                    ],
                ],
            ],
            'reindex asynchronously if there are more messages than the batch size on 3 websites and 2 entities' => [
                'message' => [
                    'granulize' => true,
                    'class' => [Product::class, Category::class],
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [1, 2, 3],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                    ],
                ],
                'classesToIndex' => [Product::class, Category::class],
                'websiteIdsToIndex' => [1, 2, 3],
                'granulizedMessages' => [
                    [
                        'class' => 'Product',
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2],
                        ],
                    ],
                    [
                        'class' => Product::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [3],
                        ],
                    ],
                    [
                        'class' => Product::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2],
                        ],
                    ],
                    [
                        'class' => Product::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [3],
                        ],
                    ],
                    [
                        'class' => Product::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [3],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2],
                        ],
                    ],
                    [
                        'class' => Product::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [3],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [3],
                        ],
                    ],
                    [
                        'class' => Category::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2],
                        ],
                    ],
                    [
                        'class' => Category::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [3],
                        ],
                    ],
                    [
                        'class' => Category::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2],
                        ],
                    ],
                    [
                        'class' => Category::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [3],
                        ],
                    ],
                    [
                        'class' => Category::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [3],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2],
                        ],
                    ],
                    [
                        'class' => Category::class,
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [3],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [3],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider getProcessExceptionsDataProvider
     */
    public function testProcessExceptions(
        \Exception|\PHPUnit\Framework\MockObject\MockObject $exception,
        int $logLevel,
        string $result
    ): void {
        $messageBody = [
            'class' => TestActivity::class,
            'context' => [
                AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2],
                AbstractIndexer::CONTEXT_WEBSITE_IDS => [1],
            ],
        ];
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message
            ->expects(self::once())
            ->method('getBody')
            ->willReturn(JSON::encode($messageBody));
        $message
            ->expects(self::any())
            ->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn(AsyncIndexer::TOPIC_REINDEX);

        $this->indexer
            ->expects(self::once())
            ->method('reindex')
            ->willThrowException($exception);

        $this->logger
            ->expects(self::once())
            ->method('log')
            ->with($logLevel, 'An unexpected exception occurred during indexation', ['exception' => $exception]);

        self::assertEquals($result, $this->processor->process($message, $this->session));
    }

    public function getProcessExceptionsDataProvider(): array
    {
        return [
            'process deadlock' => [
                'exception' => $this->createMock(DeadlockException::class),
                'logLevel' => Logger::WARNING,
                'result' => MessageProcessorInterface::REQUEUE,
            ],
            'process exception' => [
                'exception' => new \Exception(),
                'logLevel' => Logger::WARNING,
                'result' => MessageProcessorInterface::REQUEUE,
            ],
            'process unique constraint exception' => [
                'exception' => $this->createMock(UniqueConstraintViolationException::class),
                'logLevel' => Logger::WARNING,
                'result' => MessageProcessorInterface::REQUEUE,
            ],
            'process foreign key constraint exception' => [
                'exception' => $this->createMock(ForeignKeyConstraintViolationException::class),
                'logLevel' => Logger::WARNING,
                'result' => MessageProcessorInterface::REQUEUE,
            ],
            'Invalid body exception' => [
                'exception' => $this->createMock(InvalidArgumentException::class),
                'logLevel' => Logger::ERROR,
                'result' => MessageProcessorInterface::REJECT,
            ],
        ];
    }
}
