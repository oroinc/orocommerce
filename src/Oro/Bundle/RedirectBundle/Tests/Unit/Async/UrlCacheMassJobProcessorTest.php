<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Async;

use Oro\Bundle\RedirectBundle\Async\SluggableEntitiesProcessor;
use Oro\Bundle\RedirectBundle\Async\Topics;
use Oro\Bundle\RedirectBundle\Async\UrlCacheMassJobProcessor;
use Oro\Bundle\RedirectBundle\Cache\UrlStorageCache;
use Oro\Bundle\RedirectBundle\Entity\Repository\SlugRepository;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Job\JobRunner;
use Oro\Component\MessageQueue\Test\JobRunner as TestJobRunner;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Oro\Component\Testing\Unit\EntityTrait;
use Psr\Log\LoggerInterface;

class UrlCacheMassJobProcessorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    const MESSAGE_ID = 'some_message_id';

    /**
     * @var TestJobRunner|\PHPUnit_Framework_MockObject_MockObject
     */
    private $jobRunner;

    /**
     * @var MessageProducerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $producer;

    /**
     * @var SlugRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $repository;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var SluggableEntitiesProcessor
     */
    private $processor;

    /**
     * @var UrlStorageCache|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cache;

    protected function setUp()
    {
        $this->jobRunner = new TestJobRunner();

        $this->producer = $this->getMockBuilder(MessageProducerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder(SlugRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cache = $this->getMockBuilder(UrlStorageCache::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->processor = new UrlCacheMassJobProcessor(
            $this->jobRunner,
            $this->producer,
            $this->repository,
            $this->logger,
            $this->cache
        );
    }

    /**
     * @param array $data
     * @return MessageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createMessage(array $data = [])
    {
        /** @var MessageInterface|\PHPUnit_Framework_MockObject_MockObject $message **/
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->any())
            ->method('getMessageId')
            ->willReturn(self::MESSAGE_ID);

        $messageBody = json_encode($data);
        $message->expects($this->any())
            ->method('getBody')
            ->willReturn($messageBody);

        return $message;
    }

    /**
     * @return SessionInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createSession()
    {
        return $this->createMock(SessionInterface::class);
    }

    public function testProcess()
    {
        $message = $this->createMessage();

        $this->cache->expects($this->once())
            ->method('deleteAll');

        $this->repository->expects($this->once())
            ->method('getUsedRoutes')
            ->willReturn(['route1']);
        $this->repository->expects($this->once())
            ->method('getSlugsCountByRoute')
            ->with('route1')
            ->willReturn(3);
        $this->repository->expects($this->once())
            ->method('getSlugIdsByRoute')
            ->with('route1', 0, UrlCacheMassJobProcessor::BATCH_SIZE)
            ->willReturn([1, 3, 5]);

        $this->producer
            ->expects($this->once())
            ->method('send')
            ->with(
                Topics::PROCESS_CALCULATE_URL_CACHE_JOB,
                ['route_name' => 'route1', 'entity_ids' => [1, 3, 5], 'jobId' => null]
            );

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->createSession())
        );
    }

    /**
     * @dataProvider batchSizeDataProvider
     * @param int|float $batchSize
     * @param int $expected
     */
    public function testBatchSize($batchSize, $expected)
    {
        $this->processor->setBatchSize($batchSize);
        $this->assertAttributeSame($expected, 'batchSize', $this->processor);
    }

    /**
     * @return array
     */
    public function batchSizeDataProvider()
    {
        return [
            'correct' => [1, 1],
            'negative' => [-1, UrlCacheMassJobProcessor::BATCH_SIZE],
            'zero' => [0, UrlCacheMassJobProcessor::BATCH_SIZE],
            'float incorrect' => [-10.5, UrlCacheMassJobProcessor::BATCH_SIZE],
            'float correct' => [10.8, 10],
        ];
    }

    public function testProcessWithChangedBatchSize()
    {
        $message = $this->createMessage();

        $this->cache->expects($this->once())
            ->method('deleteAll');

        $this->repository->expects($this->once())
            ->method('getUsedRoutes')
            ->willReturn(['route1']);
        $this->repository->expects($this->once())
            ->method('getSlugsCountByRoute')
            ->with('route1')
            ->willReturn(5);
        $this->repository->expects($this->exactly(2))
            ->method('getSlugIdsByRoute')
            ->withConsecutive(
                ['route1', 0, 3],
                ['route1', 1, 3]
            )
            ->willReturnOnConsecutiveCalls(
                [1, 3, 5],
                [6, 7]
            );

        $this->producer
            ->expects($this->exactly(2))
            ->method('send')
            ->withConsecutive(
                [
                    Topics::PROCESS_CALCULATE_URL_CACHE_JOB,
                    ['route_name' => 'route1', 'entity_ids' => [1, 3, 5], 'jobId' => null]
                ],
                [
                    Topics::PROCESS_CALCULATE_URL_CACHE_JOB,
                    ['route_name' => 'route1', 'entity_ids' => [6, 7], 'jobId' => null]
                ]
            );

        $this->processor->setBatchSize(3);

        $this->assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->createSession())
        );
    }

    /**
     * @dataProvider jobRunnerDataProvider
     * @param bool $jobResult
     * @param string $expectedResult
     */
    public function testProcessWhenJobRunnerMocked($jobResult, $expectedResult)
    {
        $message = $this->createMessage();
        $jobRunner = $this->getMockBuilder(JobRunner::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cache->expects($this->once())
            ->method('deleteAll');

        /** @var JobRunner|\PHPUnit_Framework_MockObject_MockObject $jobRunner */
        $jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with(
                self::MESSAGE_ID,
                Topics::CALCULATE_URL_CACHE_MASS,
                $this->callback(function (callable $callable) {
                    return true;
                })
            )
            ->willReturn($jobResult);

        $this->processor = new UrlCacheMassJobProcessor(
            $jobRunner,
            $this->producer,
            $this->repository,
            $this->logger,
            $this->cache
        );

        $this->assertEquals($expectedResult, $this->processor->process($message, $this->createSession()));
    }

    /**
     * @return array
     */
    public function jobRunnerDataProvider()
    {
        return [
            'job runner succeeded' => [
                'jobResult' => true,
                'expectedResult' => MessageProcessorInterface::ACK
            ],
            'job runner failed' => [
                'jobResult' => false,
                'expectedResult' => MessageProcessorInterface::REJECT
            ]
        ];
    }

    public function testProcessWhenExceptionWasThrown()
    {
        $data = ['some' => 'data'];
        $message = $this->createMessage($data);
        $jobRunner = $this->getMockBuilder(JobRunner::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->cache->expects($this->once())
            ->method('deleteAll');

        $exception = new \Exception();
        /** @var JobRunner|\PHPUnit_Framework_MockObject_MockObject $jobRunner */
        $jobRunner
            ->expects($this->once())
            ->method('runUnique')
            ->with(
                self::MESSAGE_ID,
                Topics::CALCULATE_URL_CACHE_MASS,
                $this->callback(function (callable $callable) {
                    return true;
                })
            )
            ->willThrowException($exception);

        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with(
                'Unexpected exception occurred during queue message processing',
                [
                    'message' => json_encode($data),
                    'topic' => Topics::CALCULATE_URL_CACHE_MASS,
                    'exception' => $exception
                ]
            );

        $this->processor = new UrlCacheMassJobProcessor(
            $jobRunner,
            $this->producer,
            $this->repository,
            $this->logger,
            $this->cache
        );

        $this->assertEquals(
            MessageProcessorInterface::REJECT,
            $this->processor->process($message, $this->createSession())
        );
    }

    public function testGetSubscribedTopics()
    {
        $this->assertEquals([Topics::CALCULATE_URL_CACHE_MASS], UrlCacheMassJobProcessor::getSubscribedTopics());
    }
}
