<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine\AsyncMessaging;

use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
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
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Util\JSON;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;

class SearchMessageProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var IndexerInterface|\PHPUnit\Framework\MockObject\MockObject $indexer
     */
    private $indexer;

    /**
     * @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $indexer
     */
    private $messageProducer;

    /**
     * @var IndexerInputValidator|\PHPUnit\Framework\MockObject\MockObject $indexer
     */
    private $indexerInputValidator;

    /**
     * @var ReindexMessageGranularizer|\PHPUnit\Framework\MockObject\MockObject $indexer
     */
    private $reindexMessageGranularizer;

    /**
     * @var SearchMessageProcessor
     */
    private $processor;

    /**
     * @var SessionInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $session;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $eventDispatcher;

    /**
     * @var SearchMappingProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $mappingProvider;

    /**
     * @var WebsiteProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $websiteProvider;

    /**
     * @var JobRunner|\PHPUnit\Framework\MockObject\MockObject
     */
    private $jobRunner;

    protected function setUp(): void
    {
        $this->indexer = $this->createMock(IndexerInterface::class);
        $this->indexer
            ->expects($this->any())
            ->method('reindex')
            ->willReturn(1);

        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->websiteProvider = $this->createMock(WebsiteProviderInterface::class);
        $this->mappingProvider = $this->createMock(SearchMappingProvider::class);
        $this->reindexMessageGranularizer = $this->createMock(ReindexMessageGranularizer::class);
        $this->jobRunner = $this->createMock(JobRunner::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->mappingProvider
            ->expects($this->any())
            ->method('isClassSupported')
            ->willReturnCallback(fn ($class) => class_exists($class, true));

        $this->indexerInputValidator = new IndexerInputValidator($this->websiteProvider, $this->mappingProvider);
        $this->processor = new SearchMessageProcessor(
            $this->indexer,
            $this->messageProducer,
            $this->indexerInputValidator,
            $this->reindexMessageGranularizer,
            $this->jobRunner,
            $this->logger,
            $this->eventDispatcher
        );

        $this->websiteProvider
            ->expects($this->any())
            ->method('getWebsiteIds')
            ->willReturn([1]);

        $this->session = $this->createMock(SessionInterface::class);
        $this->eventDispatcher
            ->expects($this->any())
            ->method('dispatch')
            ->willReturnCallback(function ($event, string $name) {
                if ($event instanceof SearchProcessingEngineExceptionEvent) {
                    $event->setConsumptionResult(MessageProcessorInterface::REQUEUE);
                }
            });
    }

    public function testProcessDelayedMessage(): void
    {
        $messageBody = ['jobId' => 1];

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);

        $message->expects($this->once())
            ->method('getBody')
            ->will($this->returnValue(json_encode($messageBody)));

        $message->expects($this->once())
            ->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn(AsyncIndexer::TOPIC_REINDEX);

        $this->jobRunner->expects($this->once())
            ->method('runDelayed')
            ->willReturn(true);

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $this->session));
    }

    /**
     * @dataProvider processingMessageDataProvider
     */
    public function testProcessingMessage($messageBody, $topic, $expectedMethod): void
    {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(JSON::encode($messageBody));
        $message
            ->expects($this->once())
            ->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn($topic);

        $this->indexer
            ->expects($this->once())
            ->method($expectedMethod);

        $this->jobRunner
            ->expects($this->never())
            ->method('runDelayed');

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $this->session));
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
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(JSON::encode($messageBody));

        $message
            ->expects($this->once())
            ->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn(AsyncIndexer::TOPIC_REINDEX);

        $this->reindexMessageGranularizer
            ->expects($this->once())
            ->method('process')
            ->with($classesToIndex, $websiteIdsToIndex, $messageBody['context'])
            ->willReturn($granulizedMessages);

        $this->indexer
            ->expects($this->exactly(count($granulizedMessages)))
            ->method('reindex');

        $this->messageProducer
            ->expects($this->never())
            ->method('send');

        $this->jobRunner
            ->expects($this->never())
            ->method('runDelayed');

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $this->session));
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
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(JSON::encode($messageBody));
        $message
            ->expects($this->once())
            ->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn(AsyncIndexer::TOPIC_REINDEX);

        $this->reindexMessageGranularizer
            ->expects($this->once())
            ->method('process')
            ->with($classesToIndex, $websiteIdsToIndex, $messageBody['context'])
            ->willReturn($granulizedMessages);

        $this->indexer
            ->expects($this->never())
            ->method('reindex');

        $this->messageProducer
            ->expects($this->exactly(count($granulizedMessages)))
            ->method('send');

        $this->jobRunner
            ->expects($this->never())
            ->method('runDelayed');

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $this->session));
    }

    public function testNotRunUniqueWhenNoInputGiven(): void
    {
        $messageBody = ['class' => null, 'context' => []];

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(JSON::encode($messageBody));
        $message
            ->expects($this->once())
            ->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn(AsyncIndexer::TOPIC_REINDEX);

        $this->jobRunner
            ->expects($this->never())
            ->method('runDelayed');

        $this->processor->process($message, $this->session);
    }

    public function testRejectOnUnsupportedTopic(): void
    {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(JSON::encode(['body']));
        $message
            ->expects($this->once())
            ->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn('unsupported-topic');

        $this->jobRunner
            ->expects($this->never())
            ->method('runDelayed');

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $this->session));
    }

    public function processingMessageDataProvider(): array
    {
        return [
            'save' => [
                'message' => [
                    'entity' => [
                        'class' => TestActivity::class,
                        'id' => 13
                    ],
                    'context' => [
                        // Check BC for AbstractIndexer::CONTEXT_WEBSITE_IDS parameter.
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3]
                    ]
                ],
                'topic' => AsyncIndexer::TOPIC_SAVE,
                'expectedMethod' => 'save'
            ],
            'delete' => [
                'message' => [
                    'entity' => [
                        'class' => TestActivity::class,
                        'id' => 13
                    ],
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [1, 2],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3]
                    ]
                ],
                'topic' => AsyncIndexer::TOPIC_DELETE,
                'expectedMethod' => 'delete'
            ],
            'reindex' => [
                'message' => [
                    'granulize' => '',
                    'class' => TestActivity::class,
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [1, 2],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3]
                    ]
                ],
                'topic' => AsyncIndexer::TOPIC_REINDEX,
                'expectedMethod' => 'reindex'
            ],
            'reindex_with_given_context' => [
                'message' => [
                    'granulize' => '',
                    'class' => TestActivity::class,
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [1, 2],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3]
                    ]
                ],
                'topic' => AsyncIndexer::TOPIC_REINDEX,
                'expectedMethod' => 'reindex'
            ],
            'resetReindex' => [
                'message' => [
                    'granulize' => '',
                    'class' => TestActivity::class,
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [1, 2],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3]
                    ]
                ],
                'topic' => AsyncIndexer::TOPIC_RESET_INDEX,
                'expectedMethod' => 'resetIndex'
            ]
        ];
    }

    public function processingReindexWithGranulizeDataProvider(): array
    {
        return [
            'reindex immediately if there are less messages than the batch size on 2 websites and 1 entity' => [
                'message' => [
                    'granulize' => 'true', // Check the BC for the presence and absence of mixed granulation value.
                    'class' => [TestActivity::class],
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [1, 2],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3]
                    ]
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
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3]
                    ]
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
     * @param \Exception|\PHPUnit\Framework\MockObject\MockObject $exception
     * @param string $result
     *
     * @dataProvider getProcessExceptionsDataProvider
     */
    public function testProcessExceptions($exception, $result): void
    {
        $messageBody = [
            'class' => TestActivity::class,
            'context' => [
                AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2],
                AbstractIndexer::CONTEXT_WEBSITE_IDS => [1],
            ]
        ];
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message
            ->expects($this->once())
            ->method('getBody')
            ->willReturn(JSON::encode($messageBody));
        $message
            ->expects($this->once())
            ->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn(AsyncIndexer::TOPIC_REINDEX);

        $this->indexer
            ->expects($this->once())
            ->method('reindex')
            ->willThrowException($exception);

        $levelMethod = $result === MessageProcessorInterface::REQUEUE ? 'warning' : 'error';
        $this->logger
            ->expects($this->once())
            ->method($levelMethod);

        $this->assertEquals($result, $this->processor->process($message, $this->session));
    }

    public function getProcessExceptionsDataProvider(): array
    {
        return [
            'process deadlock' => [
                'exception' => $this->createMock(DeadlockException::class),
                'result' => MessageProcessorInterface::REQUEUE
            ],
            'process exception' => [
                'exception' => new \Exception(),
                'result' => MessageProcessorInterface::REQUEUE
            ],
            'process unique constraint exception' => [
                'exception' => $this->createMock(UniqueConstraintViolationException::class),
                'result' => MessageProcessorInterface::REQUEUE
            ],
            'process foreign key constraint exception' => [
                'exception' => $this->createMock(ForeignKeyConstraintViolationException::class),
                'result' => MessageProcessorInterface::REQUEUE
            ],
            'Invalid body exception' => [
                'exception' => $this->createMock(InvalidArgumentException::class),
                'result' => MessageProcessorInterface::REJECT
            ]
        ];
    }
}
