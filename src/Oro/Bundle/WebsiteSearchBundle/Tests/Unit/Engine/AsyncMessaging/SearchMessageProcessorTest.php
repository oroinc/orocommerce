<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine\AsyncMessaging;

use Doctrine\DBAL\Driver\AbstractDriverException;
use Doctrine\DBAL\Exception\DeadlockException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging\ReindexMessageGranularizer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging\SearchMessageProcessor;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerInputValidator;
use Oro\Component\MessageQueue\Client\Config as MessageQueConfig;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

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

    public function setUp()
    {
        $this->indexer = $this->createMock(IndexerInterface::class);
        $this->indexer
            ->method('reindex')
            ->willReturn(1);

        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->indexerInputValidator = $this->getMockBuilder(IndexerInputValidator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->reindexMessageGranularizer = $this->getMockBuilder(ReindexMessageGranularizer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new SearchMessageProcessor(
            $this->indexer,
            $this->messageProducer,
            $this->indexerInputValidator,
            $this->reindexMessageGranularizer,
            $this->logger
        );

        $this->session = $this->createMock(SessionInterface::class);
    }

    /**
     * @param $messageBody
     * @param $topic
     * @param $expectedMethod
     *
     * @dataProvider processingMessageDataProvider
     */
    public function testProcessingMessage($messageBody, $topic, $expectedMethod)
    {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);

        $message->method('getBody')
            ->will($this->returnValue(json_encode($messageBody)));

        $message->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn($topic);

        $this->indexer->expects($this->once())
            ->method($expectedMethod);

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $this->session));
    }

    /**
     * @param array $messageBody
     * @param array $classesToIndex
     * @param array $websiteIdsToIndex
     * @param array $granulizedMessages
     *
     * @dataProvider processingReindexWithGranulizeDataProvider
     */
    public function testProcessingReindexWithGranulize(
        array $messageBody,
        array $classesToIndex,
        array $websiteIdsToIndex,
        array $granulizedMessages
    ) {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);

        $message->method('getBody')
            ->will($this->returnValue(json_encode($messageBody)));

        $message->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn(AsyncIndexer::TOPIC_REINDEX);

        $this->indexerInputValidator->expects($this->once())
            ->method('validateRequestParameters')
            ->with($messageBody['class'], $messageBody['context'])
            ->willReturn([$classesToIndex, $websiteIdsToIndex]);

        $this->reindexMessageGranularizer->expects($this->once())
            ->method('process')
            ->with($classesToIndex, $websiteIdsToIndex, $messageBody['context'])
            ->willReturn($granulizedMessages);

        $this->indexer->expects($this->exactly(count($granulizedMessages)))
            ->method('reindex');

        $this->messageProducer->expects($this->never())
            ->method('send');

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $this->session));
    }

    /**
     * @param array $messageBody
     * @param array $classesToIndex
     * @param array $websiteIdsToIndex
     * @param array $granulizedMessages
     *
     * @dataProvider processingReindexWithGranulizeAsyncDataProvider
     */
    public function testProcessingReindexWithGranulizeAsync(
        array $messageBody,
        array $classesToIndex,
        array $websiteIdsToIndex,
        array $granulizedMessages
    ) {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);

        $message->method('getBody')
            ->will($this->returnValue(json_encode($messageBody)));

        $message->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn(AsyncIndexer::TOPIC_REINDEX);

        $this->indexerInputValidator->expects($this->once())
            ->method('validateRequestParameters')
            ->with($messageBody['class'], $messageBody['context'])
            ->willReturn([$classesToIndex, $websiteIdsToIndex]);

        $this->reindexMessageGranularizer->expects($this->once())
            ->method('process')
            ->with($classesToIndex, $websiteIdsToIndex, $messageBody['context'])
            ->willReturn($granulizedMessages);

        $this->indexer->expects($this->never())
            ->method('reindex');

        $this->messageProducer->expects($this->exactly(count($granulizedMessages)))
            ->method('send');

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, $this->session));
    }

    public function testNotRunUniqueWhenNoInputGiven()
    {
        $messageBody = ['class' => null, 'context' => []];

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);

        $message->method('getBody')
            ->will($this->returnValue(json_encode($messageBody)));

        $message->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn(AsyncIndexer::TOPIC_REINDEX);

        $message->method('getMessageId')
            ->willReturn(1);

        /** @var JobRunner|\PHPUnit\Framework\MockObject\MockObject $jobRunner */
        $jobRunner = $this->createMock(JobRunner::class);

        $jobRunner->expects($this->never())
            ->method('runUnique');

        $this->processor->process($message, $this->session);
    }

    public function testRejectOnUnsupportedTopic()
    {
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);

        $message->method('getBody')
            ->will($this->returnValue(json_encode('body')));

        $message->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn('unsupported-topic');

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $this->session));
    }

    public function testRejectOnJobMessage()
    {
        $messageBody = ['class' => null, 'context' => [], 'jobId' => 1];

        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);

        $message->method('getBody')
            ->will($this->returnValue(json_encode($messageBody)));

        $message->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn(AsyncIndexer::TOPIC_REINDEX);

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $this->session));
    }

    /**
     * @return array
     */
    public function processingMessageDataProvider()
    {
        return [
            'save'                       => [
                'message'        => [
                    'entity'  => [
                        'class' => '\StdClass',
                        'id'    => 13
                    ],
                    'context' => []
                ],
                'topic'          => AsyncIndexer::TOPIC_SAVE,
                'expectedMethod' => 'save'
            ],
            'delete'                     => [
                'message'        => [
                    'entity'  => [
                        'class' => '\StdClass',
                        'id'    => 13
                    ],
                    'context' => []
                ],
                'topic'          => AsyncIndexer::TOPIC_DELETE,
                'expectedMethod' => 'delete'
            ],
            'reindex'                    => [
                'message'        => [
                    'class'   => '\StdClass',
                    'context' => []
                ],
                'topic'          => AsyncIndexer::TOPIC_REINDEX,
                'expectedMethod' => 'reindex'
            ],
            'reindex_with_given_context' => [
                'message'        => [
                    'class'   => '\StdClass',
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS      => [1, 2],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3]
                    ]
                ],
                'topic'          => AsyncIndexer::TOPIC_REINDEX,
                'expectedMethod' => 'reindex'
            ],
            'resetReindex'               => [
                'message'        => [
                    'class'   => '\StdClass',
                    'context' => []
                ],
                'topic'          => AsyncIndexer::TOPIC_RESET_INDEX,
                'expectedMethod' => 'resetIndex'
            ]
        ];
    }

    /**
     * @return array
     */
    public function processingReindexWithGranulizeDataProvider()
    {
        return [
            'reindex immediately if there are less messages than the batch size on 2 websites and 1 entity' => [
                'message'        => [
                    'granulize'=> true,
                    'class'   => '\StdClass',
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS      => [1, 2],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3]
                    ]
                ],
                'classesToIndex' => ['\StdClass'],
                'websiteIdsToIndex'=> [1, 2],
                'granulizedMessages' => [
                    [
                        'class' => 'Product',
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                        ],
                    ],
                    [
                        'class' => 'Product',
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                        ],
                    ],
                ],
            ],
            'reindex immediately if there are less messages than the batch size on 3 websites and 2 entities' => [
                'message'        => [
                    'granulize'=> true,
                    'class'   => ['Product', 'Category'],
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS      => [1, 2, 3],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3]
                    ]
                ],
                'classesToIndex' => ['Product', 'Category'],
                'websiteIdsToIndex'=> [1, 2, 3],
                'granulizedMessages' => [
                    [
                        'class' => 'Product',
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                        ],
                    ],
                    [
                        'class' => 'Product',
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                        ],
                    ],
                    [
                        'class' => 'Product',
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [3],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                        ],
                    ],
                    [
                        'class' => 'Category',
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                        ],
                    ],
                    [
                        'class' => 'Category',
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                        ],
                    ],
                    [
                        'class' => 'Category',
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
     * @return array
     */
    public function processingReindexWithGranulizeAsyncDataProvider()
    {
        return [
            'reindex asynchronously if there are more messages than the batch size on 2 websites and 1 entity' => [
                'message' => [
                    'granulize' => true,
                    'class' => '\StdClass',
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [1, 2],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                    ],
                ],
                'classesToIndex' => ['\StdClass'],
                'websiteIdsToIndex' => [1, 2],
                'granulizedMessages' => [
                    [
                        'class' => 'Product',
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2],
                        ],
                    ],
                    [
                        'class' => 'Product',
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [3],
                        ],
                    ],
                    [
                        'class' => 'Product',
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2],
                        ],
                    ],
                    [
                        'class' => 'Product',
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
                    'class' => ['Product', 'Category'],
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [1, 2, 3],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3],
                    ],
                ],
                'classesToIndex' => ['Product', 'Category'],
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
                        'class' => 'Product',
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [3],
                        ],
                    ],
                    [
                        'class' => 'Product',
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2],
                        ],
                    ],
                    [
                        'class' => 'Product',
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [3],
                        ],
                    ],
                    [
                        'class' => 'Product',
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [3],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2],
                        ],
                    ],
                    [
                        'class' => 'Product',
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [3],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [3],
                        ],
                    ],
                    [
                        'class' => 'Category',
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2],
                        ],
                    ],
                    [
                        'class' => 'Category',
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [1],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [3],
                        ],
                    ],
                    [
                        'class' => 'Category',
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2],
                        ],
                    ],
                    [
                        'class' => 'Category',
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [2],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [3],
                        ],
                    ],
                    [
                        'class' => 'Category',
                        'context' => [
                            AbstractIndexer::CONTEXT_WEBSITE_IDS => [3],
                            AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2],
                        ],
                    ],
                    [
                        'class' => 'Category',
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
     * @param bool   $isDeadlock
     * @param string $result
     *
     * @dataProvider getProcessExceptionsDataProvider
     */
    public function testProcessExceptions($exception, $isDeadlock, $result)
    {
        $messageBody = [
            'class' => '\StdClass',
            'context' => []
        ];
        /** @var MessageInterface|\PHPUnit\Framework\MockObject\MockObject $message */
        $message = $this->createMock(MessageInterface::class);
        $message->method('getBody')
            ->will($this->returnValue(json_encode($messageBody)));
        $message->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn(AsyncIndexer::TOPIC_REINDEX);

        $this->indexer->expects($this->once())
            ->method('reindex')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error');

        $this->assertEquals($result, $this->processor->process($message, $this->session));
    }

    /**
     * @return array
     */
    public function getProcessExceptionsDataProvider()
    {
        return [
            'process deadlock' => [
                'exception' => $this->createMock(DeadlockException::class),
                'isDeadlock' => true,
                'result' => MessageProcessorInterface::REQUEUE
            ],
            'process exception' => [
                'exception' => new \Exception(),
                'isDeadlock' => false,
                'result' => MessageProcessorInterface::REJECT
            ],
            'process unique constraint exception' => [
                'exception' => $this->createMock(UniqueConstraintViolationException::class),
                'isDeadlock' => false,
                'result' => MessageProcessorInterface::REQUEUE
            ],
            'process foreign key constraint exception' => [
                'exception' => $this->createMock(ForeignKeyConstraintViolationException::class),
                'isDeadlock' => false,
                'result' => MessageProcessorInterface::REQUEUE
            ]
        ];
    }
}
