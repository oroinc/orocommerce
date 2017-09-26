<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine\AsyncMessaging;

use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging\SearchMessageProcessor;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\IndexerException;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Client\Config as MessageQueConfig;
use Psr\Log\LoggerInterface;

class SearchMessageProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var IndexerInterface|\PHPUnit_Framework_MockObject_MockObject $indexer
     */
    private $indexer;

    /**
     * @var SearchMessageProcessor
     */
    private $processor;

    /**
     * @var SessionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $session;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    public function setUp()
    {
        $this->indexer = $this->createMock(IndexerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new SearchMessageProcessor($this->indexer, $this->logger);

        $this->session = $this->createMock(SessionInterface::class);
    }

    /**
     * @dataProvider processingMessageDataProvider
     */
    public function testProcessingMessage($messageBody, $topic, $expectedMethod)
    {
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

    public function testRejectOnUnsupportedTopic()
    {
        $message = $this->createMock(MessageInterface::class);

        $message->method('getBody')
            ->will($this->returnValue(json_encode('body')));

        $message->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn('unsupported-topic');

        $this->assertEquals(MessageProcessorInterface::REJECT, $this->processor->process($message, $this->session));
    }

    /**
     * @param $messageBody
     * @param $topic
     * @param $expectedMethod
     *
     * @dataProvider processingMessageDataProvider
     */
    public function testIndexerException($messageBody, $topic, $expectedMethod)
    {
        $expectedExceptionMessage = '';
        $message = $this->createMock(MessageInterface::class);

        $message->method('getBody')
            ->will($this->returnValue(json_encode($messageBody)));

        $message->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn($topic);

        $this->indexer
            ->expects(static::once())
            ->method($expectedMethod)
            ->willThrowException(new IndexerException($expectedExceptionMessage));

        $this->logger
            ->expects(static::once())
            ->method('error')
            ->with($expectedExceptionMessage);

        $this->assertEquals(MessageProcessorInterface::REQUEUE, $this->processor->process($message, $this->session));
    }

    /**
     * @return array
     */
    public function processingMessageDataProvider()
    {
        return [
            'save' => [
                'message' =>[
                    'entity' => [
                        'class' => '\StdClass',
                        'id' => 13
                    ],
                    'context' => []
                ],
                'topic' => AsyncIndexer::TOPIC_SAVE,
                'expectedMethod' => 'save'
            ],
            'delete' => [
                'message' =>[
                    'entity' => [
                        'class' => '\StdClass',
                        'id' => 13
                    ],
                    'context' => []
                ],
                'topic' => AsyncIndexer::TOPIC_DELETE,
                'expectedMethod' => 'delete'
            ],
            'reindex' => [
                'message' =>[
                    'class' => '\StdClass',
                    'context' => []
                ],
                'topic' => AsyncIndexer::TOPIC_REINDEX,
                'expectedMethod' => 'reindex'
            ],
            'resetReindex' => [
                'message' =>[
                    'class' => '\StdClass',
                    'context' => []
                ],
                'topic' => AsyncIndexer::TOPIC_RESET_INDEX,
                'expectedMethod' => 'resetIndex'
            ]
        ];
    }
}
