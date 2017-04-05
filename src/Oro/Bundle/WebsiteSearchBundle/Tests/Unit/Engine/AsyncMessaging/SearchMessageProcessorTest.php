<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine\AsyncMessaging;

use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncMessaging\SearchMessageProcessor;
use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Test\JobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\MessageQueue\Client\Config as MessageQueConfig;

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

    public function setUp()
    {
        $this->indexer = $this->createMock(IndexerInterface::class);
        $this->indexer
            ->method('reindex')
            ->willReturn(1);

        $this->processor = new SearchMessageProcessor($this->indexer, new JobRunner());

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
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
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
     * @param $messageBody
     * @param $messageId
     * @param $jobName
     *
     * @dataProvider buildJobNameForMessageDataProvider
     */
    public function testBuildJobNameForMessage($messageBody, $messageId, $jobName)
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->createMock(MessageInterface::class);

        $message->method('getBody')
            ->will($this->returnValue(json_encode($messageBody)));

        $message->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn(AsyncIndexer::TOPIC_REINDEX);

        $message->method('getMessageId')
            ->willReturn($messageId);

        /** @var JobRunner|\PHPUnit_Framework_MockObject_MockObject $jobRunner */
        $jobRunner = $this->createMock(JobRunner::class);

        $this->processor = new SearchMessageProcessor($this->indexer, $jobRunner);

        $jobRunner->expects($this->once())
            ->method('runUnique')
            ->with($messageId, $jobName);

        $this->processor->process($message, $this->session);
    }

    public function testNotRunUniqueWhenNoInputGiven()
    {
        $messageBody = ['class' => null, 'context' => []];

        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->createMock(MessageInterface::class);

        $message->method('getBody')
            ->will($this->returnValue(json_encode($messageBody)));

        $message->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn(AsyncIndexer::TOPIC_REINDEX);

        $message->method('getMessageId')
            ->willReturn(1);

        /** @var JobRunner|\PHPUnit_Framework_MockObject_MockObject $jobRunner */
        $jobRunner = $this->createMock(JobRunner::class);

        $this->processor = new SearchMessageProcessor($this->indexer, $jobRunner);

        $jobRunner->expects($this->never())
            ->method('runUnique');

        $this->processor->process($message, $this->session);
    }

    public function testRejectOnUnsupportedTopic()
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message */
        $message = $this->createMock(MessageInterface::class);

        $message->method('getBody')
            ->will($this->returnValue(json_encode('body')));

        $message->method('getProperty')
            ->with(MessageQueConfig::PARAMETER_TOPIC_NAME)
            ->willReturn('unsupported-topic');

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
    public function buildJobNameForMessageDataProvider()
    {
        return [
            [
                'messageBody' => [
                    'class'   => ['Product'],
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS      => [1, 2, 3],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [],
                    ]
                ],
                'messageId'   => 1,
                'jobName'     => 'website_search_reindex|a48239eb5ecaa2b782ff2cbbf0d34533'
            ],
            [
                'messageBody' => [
                    'class'   => ['Product'],
                    'context' => []
                ],
                'messageId'   => 2,
                'jobName'     => 'website_search_reindex|4223c0b13a1961eec345321c3a05c7e6'
            ],
            [
                'messageBody' => [
                    'class'   => ['Product'],
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS      => [1, 2],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3, 4],
                    ]
                ],
                'messageId'   => 3,
                'jobName'     => 'website_search_reindex|c6b774eac4cfdba461ace1186ee2640a'
            ],
            [
                'messageBody' => [
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS      => [1, 2],
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3, 4],
                    ]
                ],
                'messageId'   => 4,
                'jobName'     => 'website_search_reindex|832e10bf6f398367916cdec1827e90cc'
            ],
            [
                'messageBody' => [
                    'class' => ['Product']
                ],
                'messageId'   => 5,
                'jobName'     => 'website_search_reindex|4223c0b13a1961eec345321c3a05c7e6'
            ],
            [
                'messageBody' => [
                    'class'   => ['Product'],
                    'context' => [
                        AbstractIndexer::CONTEXT_WEBSITE_IDS => [1, 2]
                    ]
                ],
                'messageId'   => 6,
                'jobName'     => 'website_search_reindex|5d068fd124226bee495078e525ea7e94'
            ],
            [
                'messageBody' => [
                    'class'   => ['Product'],
                    'context' => [
                        AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [1, 2, 3, 4],
                    ]
                ],
                'messageId'   => 7,
                'jobName'     => 'website_search_reindex|3f728306313430c89c56da58bec7dd5f'
            ],
        ];
    }
}
